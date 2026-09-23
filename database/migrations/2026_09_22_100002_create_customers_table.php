<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 20)->unique();
            $table->string('customer_name', 255);
            $table->foreignId('customer_group_id')->constrained()->cascadeOnDelete();
            $table->enum('customer_type', ['DISTRIBUTOR', 'CORPORATE', 'RETAIL', 'GOVERNMENT', 'RESELLER', 'INTERNAL'])->default('CORPORATE');
            $table->string('tax_number', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->foreignId('payment_term_id')->constrained()->cascadeOnDelete();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('customer_group_id');
            $table->index('payment_term_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
