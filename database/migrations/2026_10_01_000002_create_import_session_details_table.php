<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_session_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('import_sessions')->cascadeOnDelete();
            $table->string('doc_number', 50);
            $table->string('change_type', 20); // new | changed | unchanged
            $table->date('transaction_date')->nullable();
            $table->string('customer_name')->nullable();
            $table->json('old_values')->nullable(); // hanya untuk change_type=changed (field yang berubah)
            $table->json('new_values')->nullable(); // snapshot baris saat import
            $table->timestamps();

            $table->index(['session_id', 'change_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_session_details');
    }
};
