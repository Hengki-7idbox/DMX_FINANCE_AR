<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->nullable()->constrained('payment_uploads')->nullOnDelete();
            $table->string('source_type', 20)->index();
            $table->string('ar_invoice_id', 50)->index();
            $table->decimal('payment_amount', 18, 2)->default(0);
            $table->decimal('pph23_amount', 18, 2)->default(0);
            $table->decimal('disctaken_amount', 18, 2)->default(0);
            $table->string('sequence_no', 50)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->decimal('cheque_amount', 18, 2)->nullable();
            $table->string('bill_to_id')->nullable();
            $table->string('transaction_id', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('void')->default(false);
            $table->timestamps();

            $table->index(['source_type', 'ar_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_lines');
    }
};
