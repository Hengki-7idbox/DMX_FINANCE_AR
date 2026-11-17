<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('kode_vendor_internal', 100)->unique();
            $table->string('kode_vendor_external', 100)->nullable();
            $table->string('nama_vendor_internal', 255)->nullable();
            $table->string('nama_vendor_external', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapping_vendors');
    }
};
