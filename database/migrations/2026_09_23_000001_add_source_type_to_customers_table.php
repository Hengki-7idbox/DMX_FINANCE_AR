<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('source_type', ['internal', 'external'])->default('external')->after('id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['customer_code']);
            $table->unique(['customer_code', 'source_type']);
            $table->index('source_type');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['source_type']);
            $table->dropUnique(['customer_code', 'source_type']);
            $table->unique('customer_code');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};
