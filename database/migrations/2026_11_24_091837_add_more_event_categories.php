<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $order = 130;

        $rows = [];

        foreach ($this->categories() as $category) {
            $rows[] = [
                'slug' => $category[0],
                'name' => $category[1],
                'name_sw' => $category[2],
                'icon' => $category[3],
                'photo' => $category[4],
                'sort_order' => $order,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $order += 10;
        }

        $existing = DB::table('event_categories')->pluck('slug')->all();

        DB::table('event_categories')->insert(array_values(array_filter(
            $rows,
            fn (array $row): bool => ! in_array($row['slug'], $existing, true),
        )));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('event_categories')
            ->whereIn('slug', array_column($this->categories(), 0))
            ->delete();
    }

    /**
     * Occasions from the wider invitation list that this stationery module can carry.
     * Graduation is left to the category an admin already created, and the template seeder fills it.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    private function categories(): array
    {
        return [
            ['holiday', 'Holiday', 'Sikukuu', 'fa-calendar-days', 'celebration.jpg'],
            ['christmas', 'Christmas', 'Krismasi', 'fa-star', 'celebration.jpg'],
            ['thanksgiving', 'Thanksgiving', 'Shukrani', 'fa-hands-praying', 'gathering.jpg'],
            ['easter', 'Easter', 'Pasaka', 'fa-dove', 'celebration.jpg'],
            ['ramadan', 'Ramadan', 'Ramadhani', 'fa-moon', 'celebration.jpg'],
            ['eid', 'Eid al-Fitr', 'Eid el-Fitr', 'fa-moon', 'celebration.jpg'],
            ['valentines', 'Valentine\'s party', 'Sherehe ya wapendanao', 'fa-heart', 'celebration.jpg'],
            ['halloween', 'Halloween', 'Halloween', 'fa-masks-theater', 'celebration.jpg'],
            ['baby_shower', 'Baby shower', 'Sherehe ya mtoto', 'fa-baby', 'gathering.jpg'],
            ['gender_reveal', 'Gender reveal', 'Kufunua jinsia', 'fa-baby', 'birthday.jpg'],
            ['baptism', 'Baptism', 'Ubatizo', 'fa-church', 'celebration.jpg'],
            ['first_communion', 'First communion', 'Komunyo ya kwanza', 'fa-church', 'celebration.jpg'],
            ['quinceanera', 'Quinceañera', 'Sherehe ya miaka 15', 'fa-crown', 'celebration.jpg'],
            ['sweet_sixteen', 'Sweet 16', 'Miaka 16', 'fa-cake-candles', 'birthday.jpg'],
            ['bridal_shower', 'Bridal shower', 'Sherehe ya bibi harusi', 'fa-gem', 'gathering.jpg'],
            ['bridesmaid', 'Be my bridesmaid', 'Mwaliko wa wasindikizaji', 'fa-gem', 'celebration.jpg'],
            ['groomsman', 'Groomsman', 'Mwaliko wa bwana harusi', 'fa-handshake', 'celebration.jpg'],
            ['rehearsal_dinner', 'Rehearsal dinner', 'Chakula cha mazoezi', 'fa-utensils', 'celebration.jpg'],
            ['housewarming', 'Housewarming', 'Kuingia nyumba mpya', 'fa-gift', 'gathering.jpg'],
            ['open_house', 'Open house', 'Nyumba wazi', 'fa-gift', 'gathering.jpg'],
            ['picnic', 'Picnic', 'Pikiniki', 'fa-gift', 'gathering.jpg'],
            ['cookout', 'Cookout', 'Kuchoma nje', 'fa-utensils', 'gathering.jpg'],
            ['brunch', 'Brunch', 'Chakula cha asubuhi', 'fa-utensils', 'gathering.jpg'],
            ['breakfast', 'Breakfast', 'Kiamsha kinywa', 'fa-utensils', 'gathering.jpg'],
            ['luncheon', 'Luncheon', 'Chakula cha mchana', 'fa-utensils', 'celebration.jpg'],
            ['banquet', 'Banquet', 'Karamu kubwa', 'fa-champagne-glasses', 'celebration.jpg'],
            ['church', 'Church', 'Ibada', 'fa-church', 'celebration.jpg'],
            ['funeral', 'Funeral', 'Mazishi', 'fa-dove', 'celebration.jpg'],
            ['celebration_of_life', 'Celebration of life', 'Maadhimisho ya maisha', 'fa-dove', 'celebration.jpg'],
            ['awards', 'Awards night', 'Usiku wa tuzo', 'fa-award', 'corporate.jpg'],
            ['black_tie', 'Black tie', 'Mavazi rasmi', 'fa-crown', 'corporate.jpg'],
            ['wine_tasting', 'Wine tasting', 'Ladha ya divai', 'fa-martini-glass-citrus', 'celebration.jpg'],
            ['masquerade', 'Masquerade', 'Tamasha la barakoa', 'fa-masks-theater', 'celebration.jpg'],
            ['sports', 'Sports', 'Michezo', 'fa-futbol', 'celebration.jpg'],
            ['football', 'Football', 'Kandanda', 'fa-futbol', 'celebration.jpg'],
            ['movie_night', 'Movie night', 'Usiku wa sinema', 'fa-masks-theater', 'celebration.jpg'],
            ['get_together', 'Get together', 'Kukutana', 'fa-handshake', 'gathering.jpg'],
            ['sleepover', 'Sleepover', 'Kulala pamoja', 'fa-moon', 'birthday.jpg'],
            ['exhibition', 'Exhibition', 'Maonyesho', 'fa-star', 'corporate.jpg'],
            ['safari', 'Safari', 'Safari', 'fa-dove', 'sendoff.jpg'],
            ['travel', 'Travel', 'Kusafiri', 'fa-calendar-check', 'sendoff.jpg'],
        ];
    }
};
