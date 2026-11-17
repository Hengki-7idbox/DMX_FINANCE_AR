<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receive_item_imports', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 255)->nullable();
            $table->string('exim_id', 50)->nullable();
            $table->string('branch_code', 50)->nullable();
            $table->unsignedInteger('total_documents')->default(0);
            $table->unsignedInteger('total_lines')->default(0);
            $table->unsignedInteger('new_documents')->default(0);
            $table->unsignedInteger('existing_documents')->default(0);
            $table->enum('duplicate_check_status', ['ok', 'failed'])->default('ok');
            $table->text('duplicate_check_message')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receive_item_imports');
    }
};
