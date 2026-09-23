<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('outstanding_amount', 15, 2)->default(0);
            $table->enum('status', ['DRAFT', 'POSTED', 'UNPAID', 'PARTIAL', 'OVERDUE', 'PAID', 'CANCELLED', 'EXCEPTION'])->default('DRAFT');
            $table->enum('source_type', ['internal', 'external'])->default('internal');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('customer_id');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('status');
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
