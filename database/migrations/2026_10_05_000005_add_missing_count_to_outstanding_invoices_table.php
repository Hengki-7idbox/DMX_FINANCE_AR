<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outstanding_invoices', function (Blueprint $table) {
            $table->unsignedInteger('missing_count')->default(0)->after('exception_at');
        });
    }

    public function down(): void
    {
        Schema::table('outstanding_invoices', function (Blueprint $table) {
            $table->dropColumn('missing_count');
        });
    }
};
