<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('events')->where('headline', 'Would Like to Envite You')->update([
            'headline' => 'We would like to invite you',
        ]);

        DB::table('events')->where('message', 'It will an amazing celebration with your presence')->update([
            'message' => 'It will be an amazing celebration with your presence.',
        ]);

        DB::table('events')->where('message', 'The best Anniversary Ever Seen')->update([
            'message' => 'The best anniversary we have ever seen.',
        ]);
    }

    public function down(): void
    {
        DB::table('events')->where('headline', 'We would like to invite you')->update([
            'headline' => 'Would Like to Envite You',
        ]);

        DB::table('events')->where('message', 'It will be an amazing celebration with your presence.')->update([
            'message' => 'It will an amazing celebration with your presence',
        ]);

        DB::table('events')->where('message', 'The best anniversary we have ever seen.')->update([
            'message' => 'The best Anniversary Ever Seen',
        ]);
    }
};
