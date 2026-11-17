<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receive_item_draft_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receive_item_draft_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('key_id', 50)->nullable();
            $table->string('item_internal', 100)->nullable();
            $table->string('item_external', 100)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity_xml', 20, 6)->default(0);
            $table->string('unit_xml', 30)->nullable();
            $table->decimal('unit_ratio', 20, 6)->default(1);
            $table->decimal('quantity', 20, 6)->default(0);
            $table->string('unit_name', 30)->nullable();
            $table->decimal('price_xml', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 6)->default(0);
            $table->decimal('disc_percent', 9, 4)->nullable();
            $table->string('warehouse_name', 100)->nullable();
            $table->enum('status', ['ok', 'warning', 'blocked'])->default('ok');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['receive_item_draft_id', 'line_no']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receive_item_draft_lines');
    }
};
