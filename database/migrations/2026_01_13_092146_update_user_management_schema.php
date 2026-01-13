<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });

        // Handle Series Table
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE series DROP CONSTRAINT IF EXISTS series_created_by_foreign');
        }

        Schema::table('series', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Handle Clippers Table
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE clippers DROP CONSTRAINT IF EXISTS clippers_created_by_foreign');
        }

        Schema::table('clippers', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('series', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->uuid('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('clippers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->uuid('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};