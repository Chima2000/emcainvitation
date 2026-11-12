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
        Schema::table('invitation_templates', function (Blueprint $table) {
            $table->string('font')->default('playfair')->after('accent');
            $table->string('layout')->default('classic')->after('font');
            $table->string('background_path')->nullable()->after('layout');
            $table->string('default_headline')->nullable()->after('background_path');
            $table->text('default_message')->nullable()->after('default_headline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitation_templates', function (Blueprint $table) {
            $table->dropColumn(['font', 'layout', 'background_path', 'default_headline', 'default_message']);
        });
    }
};
