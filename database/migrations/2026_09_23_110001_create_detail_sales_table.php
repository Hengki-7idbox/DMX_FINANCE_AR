<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_sales', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['internal', 'external'])->default('external');
            $table->date('transaction_date')->nullable();
            $table->string('doc_number', 50)->nullable();
            $table->string('customer_code', 50)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->decimal('qty', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['source_type', 'transaction_date']);
            $table->index(['source_type', 'customer_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_sales');
    }
};
