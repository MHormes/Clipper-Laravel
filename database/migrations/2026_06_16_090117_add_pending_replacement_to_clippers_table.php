<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clippers', function (Blueprint $table) {
            $table->string('pending_image_data')->nullable()->after('image_data');
            $table->uuid('original_accepted_by')->nullable()->after('accepted_by');
            $table->foreign('original_accepted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clippers', function (Blueprint $table) {
            $table->dropForeign(['original_accepted_by']);
            $table->dropColumn(['pending_image_data', 'original_accepted_by']);
        });
    }
};
