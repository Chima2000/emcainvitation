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
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('invitation_template_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('package_guest_limit')->default(0)->after('package_id');
            $table->unsignedTinyInteger('package_reminder_rounds')->default(0)->after('package_guest_limit');
            $table->boolean('package_includes_thanks')->default(false)->after('package_reminder_rounds');
            $table->unsignedInteger('package_price_paid')->default(0)->after('package_includes_thanks');
            $table->timestamp('package_purchased_at')->nullable()->after('package_price_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn([
                'package_guest_limit',
                'package_reminder_rounds',
                'package_includes_thanks',
                'package_price_paid',
                'package_purchased_at',
            ]);
        });
    }
};
