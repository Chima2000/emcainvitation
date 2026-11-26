<?php

namespace App;

enum EventCategoryGroup: string
{
    case Weddings = 'weddings';
    case Faith = 'faith';
    case Festivals = 'festivals';
    case Gatherings = 'gatherings';
    case Work = 'work';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Weddings => __('Weddings and milestones'),
            self::Faith => __('Faith and remembrance'),
            self::Festivals => __('Festivals'),
            self::Gatherings => __('Gatherings'),
            self::Work => __('Work and formal'),
            self::Other => __('Other'),
        };
    }

    /**
     * Categories that already existed before an admin could choose a group.
     *
     * @return list<string>
     */
    public function legacySlugs(): array
    {
        return match ($this) {
            self::Weddings => ['wedding', 'muslim_wedding', 'sendoff', 'kitchen_party', 'bridal_shower', 'bridesmaid', 'groomsman', 'rehearsal_dinner', 'anniversary', 'save_the_date', 'graduation', 'birthday', 'sweet_sixteen', 'quinceanera', 'baby_shower', 'gender_reveal'],
            self::Faith => ['church', 'baptism', 'first_communion', 'ramadan', 'eid', 'funeral', 'celebration_of_life'],
            self::Festivals => ['holiday', 'christmas', 'thanksgiving', 'easter', 'valentines', 'halloween'],
            self::Gatherings => ['get_together', 'picnic', 'cookout', 'brunch', 'breakfast', 'luncheon', 'banquet', 'gala_dinner', 'wine_tasting', 'movie_night', 'sleepover', 'sports', 'football', 'safari', 'travel', 'housewarming', 'open_house', 'exhibition'],
            self::Work => ['corporate', 'contribution', 'awards', 'black_tie', 'masquerade'],
            self::Other => ['other'],
        };
    }
}
