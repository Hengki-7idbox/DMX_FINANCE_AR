<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coretax_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('kata', 255)->unique();
            $table->timestamps();
        });

        // Default dari Excel "Efaktur Code" (Table5[Kata Kunci]).
        // Jika item description invoice mengandung salah satu kata ini → TrxCode 04, selain itu 08.
        $defaults = [
            'Lotus Root Slice',
            'Peanut 20',
            'tahu',
            'tofu',
            'cocotumb',
            'kelapa',
            'peeled stem letuce',
            'Mangga Kering',
            'Thousand Flour',
            'slice',
            'Lotus Root Slice - 10kg (20pack*500gr)',
            'Kelapa Kulit Coco Thumb/12',
        ];

        foreach (array_unique($defaults) as $kata) {
            DB::table('coretax_keywords')->insert([
                'kata' => $kata,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coretax_keywords');
    }
};
