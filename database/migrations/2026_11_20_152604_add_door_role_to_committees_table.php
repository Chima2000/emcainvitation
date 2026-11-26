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
        Schema::table('committees', function (Blueprint $table) {
            $table->boolean('is_door')->default(false)->after('name');
            $table->foreignId('door_guest_id')->nullable()->after('leader_guest_id')->constrained('guests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('committees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('door_guest_id');
            $table->dropColumn('is_door');
        });
    }
};
