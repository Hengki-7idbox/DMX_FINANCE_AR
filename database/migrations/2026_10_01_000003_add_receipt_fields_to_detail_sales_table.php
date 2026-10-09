<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_sales', function (Blueprint $table) {
            $table->boolean('is_received')->default(false);
            $table->longText('received_note')->nullable();
            $table->timestamp('received_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('detail_sales', function (Blueprint $table) {
            $table->dropColumn(['is_received', 'received_note', 'received_at']);
        });
    }
};
