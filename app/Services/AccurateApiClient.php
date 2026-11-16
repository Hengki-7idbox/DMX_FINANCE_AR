<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\AccurateBulkResponse;
use Illuminate\Http\Client\Pool;
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

    /**
     * Identitas database dari api-token.do (alias, id, aplikasi, user token).
     * Di-cache 1 jam; mengembalikan null bila token belum diisi atau Accurate gagal.
     *
     * @return array{alias: ?string, database_id: ?int, application: ?string, user: ?string}|null
     */
    public function identity(): ?array
    {
        $cached = Cache::get('accurate.identity');
        if (is_array($cached)) {
            return $cached;
        }

        if (! $this->configured()) {
            return null;
        }

        $result = $this->request('POST', 'https://account.accurate.id/api/api-token.do', new stdClass());
        if (! ($result['ok'] ?? false)) {
            return null;
        }

        $d = $result['body']['d'] ?? [];
        $identity = [
            'alias' => is_string($d['database']['alias'] ?? null) ? $d['database']['alias'] : null,
            'database_id' => isset($d['database']['id']) ? (int) $d['database']['id'] : null,
            'application' => is_string($d['application']['name'] ?? null) ? $d['application']['name'] : null,
            'user' => is_string($d['user']['fullName'] ?? null) ? $d['user']['fullName'] : null,
        ];

        Cache::put('accurate.identity', $identity, 3600);

        return $identity;
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
     * Buat banyak dokumen sekaligus lewat endpoint resmi bulk-save.
     *
     * Body `{ "data": [ ... ] }`, respons array per dokumen
     * `[{ "s": true, "d": {...} }, { "s": false, "d": ["pesan"] }]`.
     * Status HTTP saja tidak cukup — tiap baris diperiksa sendiri.
     *
     * @param  array<int,array<string,mixed>>  $payloads
     * @return array{ok:bool,status:int,results:array<int,array>,message:?string,unsupported:bool}
     */
    public function bulkSave(string $module, array $payloads): array
    {
        $payloads = array_values($payloads);

        if ($payloads === []) {
            return ['ok' => true, 'status' => 200, 'results' => [], 'message' => null, 'unsupported' => false];
        }

        $attempt = 0;
        do {
            $attempt++;
            $result = $this->request(
                'POST',
                $this->host() . '/accurate/api/' . $module . '/bulk-save.do',
                ['data' => $payloads]
            );

            // Batas Accurate 8 request/detik — beri jeda lalu ulangi sekali-dua kali.
            if ((int) ($result['status'] ?? 0) === 429 && $attempt <= 2) {
                usleep(350000 * $attempt);
                continue;
            }

            break;
        } while (true);

        $status = (int) ($result['status'] ?? 0);
        $body = is_array($result['body'] ?? null) ? $result['body'] : [];

        return $this->parseBulkResponse($status, $body, count($payloads), $result['message'] ?? null);
    }

    /**
     * Bulk-save beberapa kelompok payload sekaligus (paralel, maks 8 request).
     *
     * Accurate memproses tiap dokumen secara berurutan di dalam satu request,
     * jadi beberapa request kecil yang paralel jauh lebih cepat daripada satu
     * request besar — terutama untuk dokumen bertanggal mundur.
     *
     * @param  array<int,array<int,array<string,mixed>>>  $groups
     * @return array<int,array{ok:bool,status:int,results:array<int,array>,message:?string,unsupported:bool}>
     */
    public function bulkSaveMany(string $module, array $groups, int $concurrency = 6): array
    {
        $groups = array_values($groups);
        $count = count($groups);

        if ($count === 0) {
            return [];
        }

        if (! $this->configured()) {
            return array_fill(0, $count, [
                'ok' => false,
                'status' => 0,
                'results' => [],
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
                'unsupported' => false,
            ]);
        }

        $host = $this->host();
        $timestamp = (string) time();
        $signature = base64_encode(hash_hmac('sha256', $timestamp, $this->secret, true));
        $headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $concurrency = max(1, min(8, $concurrency));
        $out = [];

        foreach (array_chunk($groups, $concurrency, true) as $chunk) {
            try {
                $responses = Http::pool(function (Pool $pool) use ($chunk, $host, $headers, $module) {
                    $promises = [];

                    foreach ($chunk as $index => $payloads) {
                        $promises[] = $pool->as((string) $index)
                            ->withHeaders($headers)
                            ->timeout(120)
                            ->post($host . '/accurate/api/' . $module . '/bulk-save.do', [
                                'data' => array_values($payloads),
                            ]);
                    }

                    return $promises;
                });
            } catch (\Throwable $e) {
                Log::error('Accurate bulk-save paralel gagal: ' . $e->getMessage());

                foreach ($chunk as $index => $payloads) {
                    $out[$index] = [
                        'ok' => false,
                        'status' => 0,
                        'results' => [],
                        'message' => 'Gagal menghubungi Accurate: ' . $e->getMessage(),
                        'unsupported' => false,
                    ];
                }

                continue;
            }

            foreach ($chunk as $index => $payloads) {
                $response = $responses[$index] ?? null;

                if ($response instanceof \Throwable) {
                    $out[$index] = [
                        'ok' => false,
                        'status' => 0,
                        'results' => [],
                        'message' => 'Gagal menghubungi Accurate: ' . $response->getMessage(),
                        'unsupported' => false,
                    ];

                    continue;
                }

                $body = $response->json();
                if (! is_array($body)) {
                    $body = ['raw' => $response->body()];
                }

                $status = (int) $response->status();

                // Kena batas 8 request/detik -> ulangi kelompok ini berurutan (ada jeda).
                if ($status === 429) {
                    $out[$index] = $this->bulkSave($module, $payloads);

                    continue;
                }

                $out[$index] = $this->parseBulkResponse(
                    $status,
                    $body,
                    count($payloads),
                    $this->errorMessage($body, $status)
                );
            }
        }

        ksort($out);

        return $out;
    }

    /**
     * @param  array<mixed>  $body
     * @return array{ok:bool,status:int,results:array<int,array>,message:?string,unsupported:bool}
     */
    private function parseBulkResponse(int $status, array $body, int $count, ?string $fallbackMessage): array
    {
        // Endpoint bulk tidak tersedia di akun ini -> pemanggil memakai save.do per dokumen.
        if (in_array($status, [404, 405], true)) {
            return [
                'ok' => false,
                'status' => $status,
                'results' => [],
                'message' => 'Endpoint bulk-save tidak tersedia.',
                'unsupported' => true,
            ];
        }

        $rows = AccurateBulkResponse::rows($body);

        if ($rows === null) {
            return [
                'ok' => false,
                'status' => $status,
                'results' => [],
                'message' => $fallbackMessage ?? 'Respons bulk Accurate tidak dikenali.',
                'unsupported' => false,
            ];
        }

        return [
            'ok' => true,
            'status' => $status,
            'results' => AccurateBulkResponse::results($rows, $count, $status),
            'message' => null,
            'unsupported' => false,
        ];
    }

    /**
     * GET beberapa endpoint sekaligus (paralel) untuk melengkapi data antar dokumen.
     *
     * @param  array<int,array{path:string,query?:array<string,mixed>}>  $requests
     * @return array<int,array{ok:bool,status:int,body:array,message:?string}>
     */
    public function getMany(array $requests, int $concurrency = 6): array
    {
        $requests = array_values($requests);
        $count = count($requests);

        if ($count === 0) {
            return [];
        }

        if (! $this->configured()) {
            return array_fill(0, $count, [
                'ok' => false,
                'status' => 0,
                'body' => [],
                'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.',
            ]);
        }

        $host = $this->host();
        $timestamp = (string) time();
        $signature = base64_encode(hash_hmac('sha256', $timestamp, $this->secret, true));
        $headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
            'Accept' => 'application/json',
        ];

        $concurrency = max(1, min(8, $concurrency));
        $out = [];

        foreach (array_chunk($requests, $concurrency, true) as $chunk) {
            try {
                $responses = Http::pool(function (Pool $pool) use ($chunk, $host, $headers) {
                    $promises = [];

                    foreach ($chunk as $index => $request) {
                        $url = $host . $request['path'];
                        $query = $request['query'] ?? [];
                        if ($query !== []) {
                            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
                        }

                        $promises[] = $pool->as((string) $index)->withHeaders($headers)->timeout(60)->get($url);
                    }

                    return $promises;
                });
            } catch (\Throwable $e) {
                Log::error('Accurate API paralel gagal: ' . $e->getMessage());

                foreach (array_keys($chunk) as $index) {
                    $out[$index] = [
                        'ok' => false,
                        'status' => 0,
                        'body' => [],
                        'message' => 'Gagal menghubungi Accurate: ' . $e->getMessage(),
                    ];
                }

                continue;
            }

            foreach (array_keys($chunk) as $index) {
                $response = $responses[$index] ?? null;

                if ($response instanceof \Throwable) {
                    $out[$index] = [
                        'ok' => false,
                        'status' => 0,
                        'body' => [],
                        'message' => 'Gagal menghubungi Accurate: ' . $response->getMessage(),
                    ];

                    continue;
                }

                $body = $response->json();
                if (! is_array($body)) {
                    $body = ['raw' => $response->body()];
                }

                $ok = $response->successful() && ($body['s'] ?? false) === true;

                $out[$index] = [
                    'ok' => $ok,
                    'status' => $response->status(),
                    'body' => $body,
                    'message' => $ok ? null : $this->errorMessage($body, $response->status()),
                ];
            }
        }

        ksort($out);

        return $out;
    }

    /**
     * Cari dokumen berdasarkan nomor (dipakai untuk guard duplikat/recovery).
     *
     * @return array|null baris pertama dari list.do
     */
    public function findByNumber(string $module, string $number): ?array
    {
        $result = $this->lookupByNumber($module, $number);

        return $result['ok'] ? $result['row'] : null;
    }

    /**
     * Seperti findByNumber(), tetapi membedakan "dokumen tidak ada" dari
     * "panggilan API gagal" supaya pemanggil tidak menghapus data lokal
     * saat Accurate sedang tidak bisa dihubungi.
     *
     * @return array{ok:bool,row:?array,message:?string}
     */
    public function lookupByNumber(string $module, string $number): array
    {
        $result = $this->get('/accurate/api/' . $module . '/list.do', [
            'filter.number.op' => 'EQUAL',
            'filter.number.val' => $number,
            'sp.pageSize' => 5,
        ]);

        if (! $result['ok']) {
            return [
                'ok' => false,
                'row' => null,
                'message' => $result['message'] ?? 'Gagal menghubungi Accurate.',
            ];
        }

        $rows = $result['body']['d'] ?? [];
        $row = is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows[0] : null;

        return ['ok' => true, 'row' => $row, 'message' => null];
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

        $message = $ok ? null : $this->errorMessage($body, $response->status());

        return [
            'ok' => $ok,
            'status' => $response->status(),
            'body' => $body,
            'message' => $message,
        ];
    }

    /**
     * Ambil pesan error dari body respons Accurate.
     */
    private function errorMessage(array $body, int $status): string
    {
        $detail = $body['d'] ?? $body['message'] ?? $body['error_description'] ?? $body['error'] ?? null;

        if (is_array($detail)) {
            return implode(' ', array_map(
                fn ($v) => is_scalar($v) ? (string) $v : (string) json_encode($v),
                $detail
            ));
        }

        if (is_string($detail) && $detail !== '') {
            return $detail;
        }

        if (isset($body['raw']) && is_string($body['raw']) && $body['raw'] !== '') {
            return mb_substr($body['raw'], 0, 300);
        }

        return 'Accurate menolak permintaan (HTTP ' . $status . ').';
    }
}
