<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coretax_item_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6);
            $table->string('name');
            $table->string('name_norm', 191)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coretax_item_codes');
    }
};
