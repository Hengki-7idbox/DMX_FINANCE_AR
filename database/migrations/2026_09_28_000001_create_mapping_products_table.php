<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_products', function (Blueprint $table) {
            $table->id();
            $table->integer('source_id')->nullable();
            $table->string('kode_item_internal', 100);
            $table->string('kode_item_external', 100);
            $table->string('code_coretax', 100)->nullable();
            $table->string('nama_item_internal', 255);
            $table->string('nama_item_external', 255);
            $table->timestamps();

            $table->index(['kode_item_internal', 'kode_item_external']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapping_products');
    }
};
