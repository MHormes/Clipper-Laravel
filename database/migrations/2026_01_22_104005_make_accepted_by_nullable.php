<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->uuid('accepted_by')->nullable()->change();
        });

        Schema::table('clippers', function (Blueprint $table) {
            $table->uuid('accepted_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->uuid('accepted_by')->nullable(false)->change();
        });

        Schema::table('clippers', function (Blueprint $table) {
            $table->uuid('accepted_by')->nullable(false)->change();
        });
    }
};
