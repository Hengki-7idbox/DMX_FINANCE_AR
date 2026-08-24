<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->enum('action_type', ['EMAIL', 'WA', 'CALL', 'NOTE', 'STATUS_CHANGE']);
            $table->text('description')->nullable();
            $table->string('action_by', 50)->nullable();
            $table->dateTime('action_date');
            $table->string('status', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('invoice_id');
            $table->index('action_date');
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_actions');
    }
};
