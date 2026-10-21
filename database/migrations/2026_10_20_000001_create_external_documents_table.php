<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_documents', function (Blueprint $table) {
            $table->id();
            $table->string('doc_number', 50);
            $table->enum('type', ['so', 'do', 'inv']);
            $table->longText('payload')->nullable();
            $table->longText('response')->nullable();
            $table->unsignedBigInteger('accurate_id')->nullable();
            $table->string('accurate_number', 50)->nullable();
            $table->string('accurate_status', 30)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['doc_number', 'type']);
            $table->index(['type', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_documents');
    }
};
