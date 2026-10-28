<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoretaxItemCode extends Model
{
    protected $fillable = ['code', 'name', 'name_norm'];

    private const COUNTRY_WORDS = [
        'china', 'korea', 'japan', 'usa', 'australia', 'new zealand', 'south africa',
        'egypt', 'peru', 'chile', 'thailand', 'vietnam', 'india', 'turkey', 'iran',
    ];

    public static function normalizeName(?string $name): string
    {
        $value = (string) $name;
        $value = (string) preg_replace('/[^\x20-\x7E]/', ' ', $value);
        $value = (string) preg_replace('/\b(' . implode('|', self::COUNTRY_WORDS) . ')\b/i', ' ', $value);
        $value = (string) preg_replace('/[^A-Za-z0-9]+/', '', $value);

        return strtolower($value);
    }

    public static function codeForName(?string $name): string
    {
        $normalized = static::normalizeName($name);
        if ($normalized === '') {
            return '';
        }

        return (string) (static::query()->where('name_norm', $normalized)->value('code') ?? '');
    }
}
