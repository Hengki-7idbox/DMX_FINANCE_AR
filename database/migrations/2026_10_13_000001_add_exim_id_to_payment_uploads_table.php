<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_uploads', function (Blueprint $table) {
            $table->string('exim_id', 50)->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('payment_uploads', function (Blueprint $table) {
            $table->dropColumn('exim_id');
        });
    }
};
