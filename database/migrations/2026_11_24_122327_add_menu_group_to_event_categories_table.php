<?php

use App\EventCategoryGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_categories', function (Blueprint $table) {
            $table->string('menu_group', 20)->default(EventCategoryGroup::Gatherings->value)->after('name_sw');
        });

        foreach (EventCategoryGroup::cases() as $group) {
            DB::table('event_categories')
                ->whereIn('slug', $group->legacySlugs())
                ->update(['menu_group' => $group->value]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_categories', function (Blueprint $table) {
            $table->dropColumn('menu_group');
        });
    }
};
