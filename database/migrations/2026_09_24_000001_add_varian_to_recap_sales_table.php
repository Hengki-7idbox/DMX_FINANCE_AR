<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recap_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('recap_sales', 'varian')) {
                $table->string('varian', 100)->nullable()->after('total_sales');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recap_sales', function (Blueprint $table) {
            if (Schema::hasColumn('recap_sales', 'varian')) {
                $table->dropColumn('varian');
            }
        });
    }
};
