<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Faktur Pajak berstatus CREATED (belum terbit) tidak punya nomor.
     * Kolom dibuat nullable supaya draft tetap bisa disimpan dan dihitung.
     * Index UNIQUE tetap dipertahankan: MySQL mengizinkan banyak NULL,
     * sementara nomor yang sudah terbit tetap unik.
     */
    public function up(): void
    {
        Schema::table('faktur_pajaks', function (Blueprint $table) {
            $table->string('tax_invoice_number', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rollback ke NOT NULL hanya bisa bila tidak ada draft tersisa.
        DB::table('faktur_pajaks')
            ->whereNull('tax_invoice_number')
            ->orWhere('tax_invoice_number', '')
            ->delete();

        Schema::table('faktur_pajaks', function (Blueprint $table) {
            $table->string('tax_invoice_number', 50)->nullable(false)->change();
        });
    }
};
