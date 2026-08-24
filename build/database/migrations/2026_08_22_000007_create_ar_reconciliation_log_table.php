<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_reconciliation_log', function (Blueprint $table) {
            $table->id();
            $table->date('recon_date');
            $table->decimal('gl_balance', 15, 2);
            $table->decimal('payment_balance', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->decimal('accuracy_pct', 5, 2);
            $table->integer('matched_count')->default(0);
            $table->integer('unmatched_count')->default(0);
            $table->string('performed_by', 50);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('recon_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_reconciliation_log');
    }
};
