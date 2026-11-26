<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Point each occasion at a photograph of that occasion.
     * A picture the admin already uploaded is left in place.
     */
    public function up(): void
    {
        foreach ($this->photos() as $slug => $photo) {
            DB::table('event_categories')
                ->where('slug', $slug)
                ->whereNull('photo_path')
                ->update(['photo' => $photo]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->previousPhotos() as $slug => $photo) {
            DB::table('event_categories')
                ->where('slug', $slug)
                ->whereNull('photo_path')
                ->update(['photo' => $photo]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function photos(): array
    {
        return [
            'muslim_wedding' => 'muslim-wedding.jpg',
            'anniversary' => 'anniversary.jpg',
            'gala_dinner' => 'gala-dinner.jpg',
            'save_the_date' => 'save-the-date.jpg',
            'holiday' => 'holiday.jpg',
            'christmas' => 'christmas.jpg',
            'thanksgiving' => 'thanksgiving.jpg',
            'easter' => 'easter.jpg',
            'ramadan' => 'ramadan.jpg',
            'eid' => 'eid.jpg',
            'valentines' => 'valentines.jpg',
            'halloween' => 'halloween.jpg',
            'baby_shower' => 'baby-shower.jpg',
            'gender_reveal' => 'gender-reveal.jpg',
            'baptism' => 'baptism.jpg',
            'first_communion' => 'first-communion.jpg',
            'quinceanera' => 'quinceanera.jpg',
            'sweet_sixteen' => 'sweet-sixteen.jpg',
            'bridesmaid' => 'gathering.jpg',
            'groomsman' => 'groomsman.jpg',
            'rehearsal_dinner' => 'rehearsal-dinner.jpg',
            'housewarming' => 'housewarming.jpg',
            'open_house' => 'open-house.jpg',
            'picnic' => 'picnic.jpg',
            'cookout' => 'cookout.jpg',
            'brunch' => 'brunch.jpg',
            'breakfast' => 'breakfast.jpg',
            'luncheon' => 'luncheon.jpg',
            'banquet' => 'banquet.jpg',
            'church' => 'church.jpg',
            'funeral' => 'funeral.jpg',
            'celebration_of_life' => 'celebration-of-life.jpg',
            'awards' => 'awards.jpg',
            'black_tie' => 'black-tie.jpg',
            'wine_tasting' => 'wine-tasting.jpg',
            'masquerade' => 'masquerade.jpg',
            'sports' => 'sports.jpg',
            'football' => 'football.jpg',
            'movie_night' => 'movie-night.jpg',
            'get_together' => 'get-together.jpg',
            'sleepover' => 'sleepover.jpg',
            'exhibition' => 'exhibition.jpg',
            'safari' => 'safari.jpg',
            'travel' => 'travel.jpg',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function previousPhotos(): array
    {
        return [
            'muslim_wedding' => 'wedding.jpg',
            'anniversary' => 'gathering.jpg',
            'gala_dinner' => 'celebration.jpg',
            'save_the_date' => 'celebration.jpg',
            'holiday' => 'celebration.jpg',
            'christmas' => 'celebration.jpg',
            'thanksgiving' => 'gathering.jpg',
            'easter' => 'celebration.jpg',
            'ramadan' => 'celebration.jpg',
            'eid' => 'celebration.jpg',
            'valentines' => 'celebration.jpg',
            'halloween' => 'celebration.jpg',
            'baby_shower' => 'gathering.jpg',
            'gender_reveal' => 'birthday.jpg',
            'baptism' => 'celebration.jpg',
            'first_communion' => 'celebration.jpg',
            'quinceanera' => 'celebration.jpg',
            'sweet_sixteen' => 'birthday.jpg',
            'bridesmaid' => 'celebration.jpg',
            'groomsman' => 'celebration.jpg',
            'rehearsal_dinner' => 'celebration.jpg',
            'housewarming' => 'gathering.jpg',
            'open_house' => 'gathering.jpg',
            'picnic' => 'gathering.jpg',
            'cookout' => 'gathering.jpg',
            'brunch' => 'gathering.jpg',
            'breakfast' => 'gathering.jpg',
            'luncheon' => 'celebration.jpg',
            'banquet' => 'celebration.jpg',
            'church' => 'celebration.jpg',
            'funeral' => 'celebration.jpg',
            'celebration_of_life' => 'celebration.jpg',
            'awards' => 'corporate.jpg',
            'black_tie' => 'corporate.jpg',
            'wine_tasting' => 'celebration.jpg',
            'masquerade' => 'celebration.jpg',
            'sports' => 'celebration.jpg',
            'football' => 'celebration.jpg',
            'movie_night' => 'celebration.jpg',
            'get_together' => 'gathering.jpg',
            'sleepover' => 'birthday.jpg',
            'exhibition' => 'corporate.jpg',
            'safari' => 'sendoff.jpg',
            'travel' => 'sendoff.jpg',
        ];
    }
};
