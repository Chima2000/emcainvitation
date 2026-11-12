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
            $table->string('failure_reason')->nullable()->after('sent_at');

            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'status']);
            $table->dropColumn('failure_reason');
        });
    }
};
