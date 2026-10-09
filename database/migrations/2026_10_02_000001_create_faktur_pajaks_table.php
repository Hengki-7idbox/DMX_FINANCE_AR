<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faktur_pajaks', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->string('buyer_tin', 20)->nullable();
            $table->string('display_name')->nullable();
            $table->string('tax_invoice_code')->nullable();
            $table->string('tax_invoice_number', 50);
            $table->date('tax_invoice_date')->nullable();
            $table->string('tax_invoice_period', 20)->nullable();
            $table->smallInteger('tax_invoice_year')->nullable();
            $table->string('tax_invoice_status', 50)->nullable();
            $table->string('e_sign_status', 50)->nullable();
            $table->decimal('selling_price', 18, 2)->default(0);
            $table->decimal('other_tax_base', 18, 2)->default(0);
            $table->decimal('vat', 18, 2)->default(0);
            $table->string('signer', 100)->nullable();
            $table->string('reference', 50)->nullable();
            $table->boolean('reported_by_seller')->default(false);
            $table->boolean('reported_by_vat_collector')->nullable();
            $table->timestamps();

            $table->unique('tax_invoice_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faktur_pajaks');
    }
};
