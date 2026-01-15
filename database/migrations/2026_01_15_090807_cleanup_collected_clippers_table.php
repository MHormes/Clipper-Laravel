<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collected_clippers', function (Blueprint $table) {
            // We check for the column first. 
            // If it exists, we drop it. PostgreSQL will automatically 
            // drop the 'NOT NULL' constraint when the column is dropped.
            if (Schema::hasColumn('collected_clippers', 'date_added')) {
                $table->dropColumn('date_added');
            }
        });
    }

    public function down(): void
    {
        Schema::table('collected_clippers', function (Blueprint $table) {
            $table->date('date_added')->nullable();
        });
    }
};