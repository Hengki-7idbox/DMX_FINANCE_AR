<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::allWithDefaults();

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'branch_code_internal' => 'nullable|string|max:50',
            'branch_code_external' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'terms_id' => 'nullable|string|max:100',
            'exim_id' => 'nullable|string|max:20',
            'tax1_id' => 'nullable|string|max:20',
            'tax2_id' => 'nullable|string|max:20',
            'tax2_rate' => 'nullable|numeric|min:0|max:100',
            'warehouse_id' => 'nullable|string|max:50',
            'ar_account' => 'nullable|string|max:50',
            'dp_account_id' => 'nullable|string|max:50',
            'seller_tin' => 'nullable|string|max:50',
            'accurate_host' => 'nullable|string|max:255',
            'accurate_warehouse_name' => 'nullable|string|max:100',
            'accurate_api_token' => 'nullable|string|max:2000',
            'accurate_signature_secret' => 'nullable|string|max:2000',
        ]);

        foreach (array_keys(Setting::defaults()) as $key) {
            Setting::put($key, $data[$key] ?? null);
        }

        Setting::put('accurate_host', ($data['accurate_host'] ?? '') ?: 'https://zeus.accurate.id');
        Setting::put('accurate_warehouse_name', ($data['accurate_warehouse_name'] ?? '') ?: 'Utama');
        Cache::forget('accurate.host');

        if (! empty($data['accurate_api_token'])) {
            Setting::put('accurate_api_token', $data['accurate_api_token']);
        }

        if (! empty($data['accurate_signature_secret'])) {
            Setting::put('accurate_signature_secret', $data['accurate_signature_secret']);
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Settings berhasil disimpan.');
    }
}
