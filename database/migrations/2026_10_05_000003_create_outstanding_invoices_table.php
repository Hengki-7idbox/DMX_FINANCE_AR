<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outstanding_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('customer_no', 50)->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_suspended', 50)->nullable();
            $table->integer('terms_net_days')->nullable();
            $table->string('invoice_no', 50)->unique();
            $table->date('invoice_date')->nullable()->index();
            $table->date('due_date')->nullable()->index();
            $table->decimal('invoice_amount', 18, 2)->default(0);
            $table->decimal('prime_owing', 18, 2)->default(0);
            $table->decimal('tax_owing', 18, 2)->default(0);
            $table->integer('age_from_due')->nullable();
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outstanding_invoices');
    }
};
