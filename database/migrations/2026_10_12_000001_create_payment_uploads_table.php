<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 20)->index();
            $table->string('file_name');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->integer('receipt_count')->default(0);
            $table->integer('line_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_uploads');
    }
};
