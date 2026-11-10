<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('file_name')->nullable();
            $table->string('file_hash', 64)->nullable()->index();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('new_count')->default(0);
            $table->unsignedInteger('changed_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('missing_count')->default(0);
            $table->string('strategy', 20)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['bank_account_id', 'period_start', 'period_end'], 'bank_import_batches_account_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_import_batches');
    }
};
