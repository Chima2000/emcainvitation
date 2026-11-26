<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('bride_name')->nullable()->after('dress_code');
            $table->string('groom_name')->nullable()->after('bride_name');
            $table->string('bride_family')->nullable()->after('groom_name');
            $table->string('groom_family')->nullable()->after('bride_family');
            $table->string('ceremony_place')->nullable()->after('groom_family');
            $table->string('reception_venue')->nullable()->after('ceremony_place');
            $table->string('reception_time', 40)->nullable()->after('reception_venue');
            $table->string('arrive_by', 80)->nullable()->after('reception_time');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'bride_name',
                'groom_name',
                'bride_family',
                'groom_family',
                'ceremony_place',
                'reception_venue',
                'reception_time',
                'arrive_by',
            ]);
        });
    }
};
