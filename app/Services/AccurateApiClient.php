<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use stdClass;

class AccurateApiClient
{
    private string $token;

    private string $secret;

    private string $fallbackHost;

    public function __construct()
    {
        $this->token = trim((string) Setting::get('accurate_api_token', ''));
        $this->secret = trim((string) Setting::get('accurate_signature_secret', ''));
        $this->fallbackHost = rtrim((string) Setting::get('accurate_host', 'https://zeus.accurate.id'), '/');
    }

    public function configured(): bool
    {
        return $this->token !== '' && $this->secret !== '';
    }

    /**
     * Host database diambil dari api-token.do (jangan hardcode), di-cache 1 jam.
     */
    public function host(): string
    {
        $cached = Cache::get('accurate.host');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $result = $this->request('POST', 'https://account.accurate.id/api/api-token.do', new stdClass());
        $host = $result['body']['d']['database']['host'] ?? null;

        if (is_string($host) && $host !== '') {
            $host = rtrim($host, '/');
            Cache::put('accurate.host', $host, 3600);

            return $host;
        }

        return $this->fallbackHost;
    }

    public function post(string $path, array $payload): array
    {
        return $this->request('POST', $this->host() . $path, $payload);
    }

    public function get(string $path, array $query = []): array
    {
        $url = $this->host() . $path;
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        return $this->request('GET', $url);
    }

    public function delete(string $module, int $id): array
    {
        return $this->post('/accurate/api/' . $module . '/delete.do', ['id' => $id]);
    }

    /**
     * Cari dokumen berdasarkan nomor (dipakai untuk guard duplikat/recovery).
     *
     * @return array|null baris pertama dari list.do
     */
    public function findByNumber(string $module, string $number): ?array
    {
        $result = $this->get('/accurate/api/' . $module . '/list.do', [
            'filter.number.op' => 'EQUAL',
            'filter.number.val' => $number,
            'sp.pageSize' => 5,
        ]);

        if (! $result['ok']) {
            return null;
        }

        $rows = $result['body']['d'] ?? [];

        return is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows[0] : null;
    }

    /**
     * @return array{ok: bool, status: int, body: array, message: ?string}
     */
    private function request(string $method, string $url, mixed $payload = null): array
    {
        if (! $this->configured()) {
            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
            ];
        }

        $timestamp = (string) time();
        $signature = base64_encode(hash_hmac('sha256', $timestamp, $this->secret, true));

        try {
            $request = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(60);

            $response = $method === 'GET'
                ? $request->get($url)
                : $request->post($url, $payload ?? new stdClass());
        } catch (\Throwable $e) {
            Log::error('Accurate API request gagal: ' . $e->getMessage(), ['url' => $url]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'message' => 'Gagal menghubungi Accurate: ' . $e->getMessage(),
            ];
        }

        $body = $response->json();
        if (! is_array($body)) {
            $body = ['raw' => $response->body()];
        }

        $ok = $response->successful() && ($body['s'] ?? false) === true;

        $message = null;
        if (! $ok) {
            $detail = $body['d'] ?? $body['message'] ?? $body['error_description'] ?? $body['error'] ?? null;

            if (is_array($detail)) {
                $message = implode(' ', array_map(
                    fn ($v) => is_scalar($v) ? (string) $v : (string) json_encode($v),
                    $detail
                ));
            } elseif (is_string($detail) && $detail !== '') {
                $message = $detail;
            } elseif (isset($body['raw']) && is_string($body['raw']) && $body['raw'] !== '') {
                $message = mb_substr($body['raw'], 0, 300);
            } else {
                $message = 'Accurate menolak permintaan (HTTP ' . $response->status() . ').';
            }
        }

        return [
            'ok' => $ok,
            'status' => $response->status(),
            'body' => $body,
            'message' => $message,
        ];
    }
}
