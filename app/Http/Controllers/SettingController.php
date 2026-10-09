<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

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
        ]);

        foreach (array_keys(Setting::defaults()) as $key) {
            Setting::put($key, $data[$key] ?? null);
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Settings berhasil disimpan.');
    }
}
