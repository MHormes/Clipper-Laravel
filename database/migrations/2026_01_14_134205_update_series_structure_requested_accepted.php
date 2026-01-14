<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    $systemId = '019bb7be-fec4-7390-a7e1-63b1a0c1067f';

    // Update SERIES
    DB::statement('ALTER TABLE series DROP CONSTRAINT IF EXISTS series_created_by_foreign');
    Schema::table('series', function (Blueprint $table) {
        $table->renameColumn('created_by', 'accepted_by');
        $table->uuid('requested_by')->nullable()->after('custom');
    });
    // Set all existing series to be requested by the system ID
    DB::table('series')->update(['requested_by' => $systemId]);
    Schema::table('series', function (Blueprint $table) {
        $table->uuid('requested_by')->nullable(false)->change();
        $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('accepted_by')->references('id')->on('users')->onDelete('cascade');
    });

    // Update CLIPPERS
    DB::statement('ALTER TABLE clippers DROP CONSTRAINT IF EXISTS clippers_created_by_foreign');
    Schema::table('clippers', function (Blueprint $table) {
        $table->renameColumn('created_by', 'accepted_by');
        $table->uuid('requested_by')->nullable()->after('series_number');
    });
    // Set all existing clippers to be requested by the system ID
    DB::table('clippers')->update(['requested_by' => $systemId]);
    Schema::table('clippers', function (Blueprint $table) {
        $table->uuid('requested_by')->nullable(false)->change();
        $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('accepted_by')->references('id')->on('users')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
        $table->dropForeign(['requested_by']);
        $table->dropColumn('requested_by');
        $table->renameColumn('accepted_by', 'created_by');
    });

    Schema::table('clippers', function (Blueprint $table) {
        $table->dropForeign(['requested_by']);
        $table->dropColumn('requested_by');
        $table->renameColumn('accepted_by', 'created_by');
    });
    }
};
