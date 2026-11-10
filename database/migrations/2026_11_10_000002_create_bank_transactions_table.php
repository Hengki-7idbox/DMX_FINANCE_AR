<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('bank_import_batches')->nullOnDelete();

            // Padanan field Baserow table 632
            $table->unsignedBigInteger('baserow_id')->nullable();
            $table->unsignedInteger('sequence_no')->nullable();
            $table->string('document_number', 60)->nullable()->index();
            $table->date('transaction_date')->index();
            $table->boolean('is_paid')->default(false);
            $table->text('description');
            $table->string('branch_code', 20)->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->boolean('has_issue')->default(false);
            $table->decimal('balance', 18, 2)->default(0);
            $table->unsignedTinyInteger('period_month')->nullable();
            $table->unsignedSmallInteger('period_year')->nullable();
            $table->string('amount_text', 40)->nullable();
            $table->string('position', 2)->index();
            $table->string('document_internal', 60)->nullable()->index();
            $table->string('document_external', 60)->nullable()->index();
            $table->text('note')->nullable();

            // Operasional aplikasi
            $table->string('status', 20)->default('NOT_FOUND')->index();
            $table->string('row_key', 64);
            $table->string('source', 20)->default('BCA_CSV');
            $table->timestamps();

            $table->unique(['bank_account_id', 'row_key']);
            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
