<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receive_item_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receive_item_import_id')->constrained()->cascadeOnDelete();
            $table->string('receive_number', 100);
            $table->string('transaction_id', 50)->nullable();
            $table->string('sequence_no', 100)->nullable();
            $table->string('vendor_internal', 100)->nullable();
            $table->string('vendor_external', 100)->nullable();
            $table->string('vendor_name', 255)->nullable();
            $table->date('trans_date')->nullable();
            $table->date('ship_date')->nullable();
            $table->date('tax_date')->nullable();
            $table->decimal('rate', 20, 6)->default(1);
            $table->string('currency_code', 10)->default('IDR');
            $table->boolean('taxable')->default(false);
            $table->boolean('inclusive_tax')->default(false);
            $table->string('payment_term_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('warehouse_name', 100)->nullable();
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->enum('status', ['new', 'exists', 'submitted', 'failed', 'invalid', 'skipped'])->default('new');
            $table->unsignedBigInteger('accurate_id')->nullable();
            $table->string('accurate_number', 100)->nullable();
            $table->string('accurate_status', 50)->nullable();
            $table->text('message')->nullable();
            $table->longText('payload')->nullable();
            $table->longText('response')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['receive_item_import_id', 'receive_number']);
            $table->index('receive_number');
            $table->index('status');
            $table->index('vendor_internal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receive_item_drafts');
    }
};
