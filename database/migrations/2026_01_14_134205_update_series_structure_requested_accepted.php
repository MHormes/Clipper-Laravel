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

        // 1. Ensure System User exists for Foreign Key integrity
        DB::table('users')->updateOrInsert(
            ['id' => $systemId],
            [
                'name' => 'System',
                'email' => 'system@clipperms.nl',
                'password' => bcrypt(Str::random(16)),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Update SERIES
        Schema::table('series', function (Blueprint $table) {
            // dropForeign is cross-db compatible in Laravel
            $table->dropForeign(['created_by']);
            $table->renameColumn('created_by', 'accepted_by');
            $table->uuid('requested_by')->nullable()->after('custom');
        });

        DB::table('series')->update(['requested_by' => $systemId]);

        Schema::table('series', function (Blueprint $table) {
            $table->uuid('requested_by')->nullable(false)->change();
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('accepted_by')->references('id')->on('users')->onDelete('cascade');
        });

        // 3. Update CLIPPERS
        Schema::table('clippers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->renameColumn('created_by', 'accepted_by');
            $table->uuid('requested_by')->nullable()->after('series_number');
        });

        DB::table('clippers')->update(['requested_by' => $systemId]);

        Schema::table('clippers', function (Blueprint $table) {
            $table->uuid('requested_by')->nullable(false)->change();
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('accepted_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['accepted_by']);
            $table->dropColumn('requested_by');
            $table->renameColumn('accepted_by', 'created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('clippers', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['accepted_by']);
            $table->dropColumn('requested_by');
            $table->renameColumn('accepted_by', 'created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};