<?php

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
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 60);
            $table->string('name_sw', 60)->nullable();
            $table->string('icon', 40)->nullable();
            $table->string('photo', 60)->default('celebration.jpg');
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $categories = [
            ['wedding', 'Weddings', 'Harusi', 'fa-ring', 'wedding.jpg'],
            ['muslim_wedding', 'Muslim weddings', 'Harusi za kiislamu', 'fa-moon', 'wedding.jpg'],
            ['sendoff', 'Sendoff', 'Sendofu', 'fa-champagne-glasses', 'sendoff.jpg'],
            ['kitchen_party', 'Kitchen party', 'Sherehe ya jikoni', 'fa-gift', 'gathering.jpg'],
            ['birthday', 'Birthdays', 'Siku za kuzaliwa', 'fa-cake-candles', 'birthday.jpg'],
            ['gala_dinner', 'Gala dinner', 'Karamu', 'fa-utensils', 'celebration.jpg'],
            ['anniversary', 'Anniversary', 'Maadhimisho', 'fa-heart', 'gathering.jpg'],
            ['save_the_date', 'Save the date', 'Weka tarehe', 'fa-calendar-check', 'celebration.jpg'],
            ['contribution', 'Contributions & meetings', 'Michango na mikutano', 'fa-hand-holding-dollar', 'corporate.jpg'],
            ['corporate', 'Corporate', 'Kampuni', 'fa-briefcase', 'corporate.jpg'],
            ['other', 'Other', 'Nyingine', 'fa-calendar-days', 'celebration.jpg'],
        ];

        $now = now();

        DB::table('event_categories')->insert(array_map(
            fn (array $category, int $index): array => [
                'slug' => $category[0],
                'name' => $category[1],
                'name_sw' => $category[2],
                'icon' => $category[3],
                'photo' => $category[4],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $categories,
            array_keys($categories),
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_categories');
    }
};
