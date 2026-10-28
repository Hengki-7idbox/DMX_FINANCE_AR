<?php

namespace App\Services;

class AccurateItemUnitService
{
    /**
     * Tambahkan satu satuan (mis. Crt) ke daftar satuan item Accurate.
     * Satuan ditaruh di slot unit2..unit5 yang masih kosong; tidak mengubah unit1.
     *
     * @return array{ok:bool,message:string,already?:bool,slot?:int,item_id?:int}
     */
    public function addUnit(string $itemNo, string $unit, float $ratio, float $price = 0.0): array
    {
        $itemNo = trim($itemNo);
        $unit = trim($unit);
        $price = max(0.0, $price);

        if ($itemNo === '' || $unit === '') {
            return ['ok' => false, 'message' => 'Kode item dan satuan wajib diisi.'];
        }

        if ($ratio <= 1) {
            return ['ok' => false, 'message' => 'Rasio harus lebih besar dari 1 (Accurate menolak rasio 1).'];
        }

        $client = new AccurateApiClient();
        if (! $client->configured()) {
            return ['ok' => false, 'message' => 'API Token dan Signature Secret Accurate belum diisi di Settings.'];
        }

        $list = $client->get('/accurate/api/item/list.do', [
            'filter.no.op' => 'EQUAL',
            'filter.no.val' => $itemNo,
            'sp.pageSize' => 1,
        ]);

        if (! $list['ok']) {
            return ['ok' => false, 'message' => $list['message'] ?? 'Gagal mencari item di Accurate.'];
        }

        $id = $list['body']['d'][0]['id'] ?? null;
        if ($id === null) {
            return ['ok' => false, 'message' => "Item {$itemNo} tidak ditemukan di Accurate."];
        }

        $detail = $client->get('/accurate/api/item/detail.do', ['id' => $id]);
        if (! $detail['ok']) {
            return ['ok' => false, 'message' => $detail['message'] ?? "Gagal membaca detail item {$itemNo}."];
        }

        $d = $detail['body']['d'] ?? [];
        if (! is_array($d) || $d === []) {
            return ['ok' => false, 'message' => "Detail item {$itemNo} kosong."];
        }

        $units = [];
        for ($i = 1; $i <= 5; $i++) {
            $name = trim((string) ($d['unit' . $i . 'Name'] ?? ''));
            if ($name !== '') {
                $units[$i] = $name;
            }
        }

        foreach ($units as $name) {
            if (strcasecmp($name, $unit) === 0) {
                return [
                    'ok' => true,
                    'already' => true,
                    'message' => "Satuan {$unit} sudah ada pada item {$itemNo}.",
                ];
            }
        }

        $slot = null;
        for ($i = 2; $i <= 5; $i++) {
            if (! isset($units[$i])) {
                $slot = $i;
                break;
            }
        }

        if ($slot === null) {
            return ['ok' => false, 'message' => "Item {$itemNo} sudah memakai 5 satuan."];
        }

        if (! $this->unitMasterExists($client, $unit)) {
            $created = $client->post('/accurate/api/unit/save.do', ['name' => $unit]);
            if (! $created['ok']) {
                return ['ok' => false, 'message' => "Master satuan {$unit} belum ada dan gagal dibuat: " . ($created['message'] ?? '-')];
            }
        }

        $payload = [
            'id' => (int) $id,
            'no' => (string) ($d['no'] ?? $itemNo),
            'name' => (string) ($d['name'] ?? ''),
            'itemType' => (string) ($d['itemType'] ?? 'INVENTORY'),
            'itemCategoryName' => (string) ($d['itemCategory']['name'] ?? ''),
            'unit1Name' => (string) ($d['unit1Name'] ?? ''),
            'unit' . $slot . 'Name' => $unit,
            'ratio' . $slot => $ratio,
            'unit' . $slot . 'Price' => $price,
        ];

        if ($payload['name'] === '' || $payload['itemCategoryName'] === '' || $payload['unit1Name'] === '') {
            return ['ok' => false, 'message' => "Data master item {$itemNo} tidak lengkap (nama/kategori/satuan 1 kosong)."];
        }

        $saved = $client->post('/accurate/api/item/save.do', $payload);
        if (! $saved['ok']) {
            return ['ok' => false, 'message' => $saved['message'] ?? 'Accurate menolak penambahan satuan.'];
        }

        return [
            'ok' => true,
            'already' => false,
            'message' => "Satuan {$unit} (rasio {$ratio}) ditambahkan ke item {$itemNo}.",
            'slot' => $slot,
            'item_id' => (int) $id,
        ];
    }

    private function unitMasterExists(AccurateApiClient $client, string $unit): bool
    {
        $list = $client->get('/accurate/api/unit/list.do', ['sp.pageSize' => 200]);
        if (! $list['ok']) {
            return false;
        }

        foreach (($list['body']['d'] ?? []) as $row) {
            if (strcasecmp(trim((string) ($row['name'] ?? '')), $unit) === 0) {
                return true;
            }
        }

        return false;
    }
}
