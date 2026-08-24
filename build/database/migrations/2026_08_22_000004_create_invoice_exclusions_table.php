<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_exclusions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->string('customer_name', 100)->nullable();
            $table->decimal('original_amount', 15, 2)->nullable();
            $table->enum('reason_code', ['DUPLICATE', 'GL_ERROR', 'TEST_INVOICE', 'DATA_ENTRY_ERROR', 'WORKFLOW_ERROR', 'OTHER']);
            $table->text('reason_detail')->nullable();
            $table->boolean('exclude_related_payment')->default(true);
            $table->string('excluded_by', 50);
            $table->dateTime('excluded_date');
            $table->enum('status', ['ACTIVE', 'REVERTED'])->default('ACTIVE');
            $table->string('revert_by', 50)->nullable();
            $table->dateTime('revert_date')->nullable();
            $table->text('revert_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('reason_code');
            $table->index('excluded_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_exclusions');
    }
};
