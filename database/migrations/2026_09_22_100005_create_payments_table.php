<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('payment_method', ['BANK_TRANSFER', 'CASH', 'CHEQUE', 'GIRO'])->default('BANK_TRANSFER');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->enum('source_type', ['internal', 'external'])->default('internal');
            $table->foreignId('source_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('customer_id');
            $table->index('payment_date');
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
