<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recap_sales', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['internal', 'external'])->default('external');
            $table->string('period', 50)->nullable();
            $table->string('customer_code', 50)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->decimal('total_sales', 18, 2)->default(0);
            $table->string('varian', 100)->nullable();
            $table->timestamps();

            $table->index(['source_type', 'period']);
            $table->index(['source_type', 'customer_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recap_sales');
    }
};
