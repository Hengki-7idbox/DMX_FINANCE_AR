<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_sales', function (Blueprint $table) {
            $table->string('fp_status')->nullable()->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('detail_sales', function (Blueprint $table) {
            $table->dropColumn('fp_status');
        });
    }
};
