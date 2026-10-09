<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('source_id')->nullable();
            $table->string('kode_internal', 100);
            $table->string('kode_external', 100);
            $table->string('nama_internal', 255);
            $table->string('nama_external', 255);
            $table->timestamps();

            $table->unique(['kode_internal', 'kode_external']);
            $table->index('kode_internal');
            $table->index('kode_external');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapping_customers');
    }
};
