<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Nilai default setiap setting (dipakai bila belum diisi di DB).
     */
    public static function defaults(): array
    {
        return [
            'branch_code_internal' => '',
            'branch_code_external' => '1127812608',
            'tax_rate' => '11',
            'terms_id' => '',
            'exim_id' => '2',
            'tax1_id' => 'P',
            'tax2_id' => 'J',
            'tax2_rate' => '12',
            'warehouse_id' => 'CENTRE',
            'ar_account' => '110311',
            'dp_account_id' => '210201',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default ?? (static::defaults()[$key] ?? null);
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Semua setting: default dioverride nilai yang tersimpan di DB.
     */
    public static function allWithDefaults(): array
    {
        $stored = static::query()->pluck('value', 'key')->all();

        return array_merge(static::defaults(), $stored);
    }
}
