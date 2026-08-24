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
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('region', 50)->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('payment_terms')->default(30);
            $table->enum('risk_profile', ['safe', 'caution', 'risk', 'blocked'])->default('safe');
            $table->enum('contact_preference', ['email', 'whatsapp', 'both', 'none'])->default('both');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();

            $table->index('code');
            $table->index('name');
            $table->index('sales_rep_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
