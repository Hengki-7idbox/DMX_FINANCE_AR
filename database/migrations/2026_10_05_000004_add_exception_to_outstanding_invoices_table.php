<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outstanding_invoices', function (Blueprint $table) {
            $table->string('exception_type', 20)->nullable()->after('invoice_no')->index();
            $table->timestamp('exception_at')->nullable()->after('exception_type');
        });
    }

    public function down(): void
    {
        Schema::table('outstanding_invoices', function (Blueprint $table) {
            $table->dropColumn(['exception_type', 'exception_at']);
        });
    }
};
