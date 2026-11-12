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
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->unsignedInteger('cost')->default(0)->after('status');
            $table->boolean('covered_by_package')->default(false)->after('cost');

            $table->index(['event_id', 'purpose', 'covered_by_package']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'purpose', 'covered_by_package']);
            $table->dropColumn(['cost', 'covered_by_package']);
        });
    }
};
