<?php

namespace App\Support;

/**
 * Aturan PPN untuk dokumen External (SO / DO / Invoice).
 *
 * Dokumen dikirim ke Accurate dengan PPN (taxable = true) — kecuali kode
 * customer external mengandung "TNPWP", yang berarti non-PPN
 * (taxable = false dan useTax1 = false pada setiap item).
 *
 * Deteksi memakai kode customer external hasil mapping, bukan kode internal,
 * karena kode internal seperti "CASH 002 - C" tidak memuat penanda TNPWP.
 */
class Taxability
{
    public const NON_PPN_MARKER = 'TNPWP';

    public static function isNonPpnCustomer(?string $externalCode): bool
    {
        return str_contains(mb_strtoupper(trim((string) $externalCode)), self::NON_PPN_MARKER);
    }
}
