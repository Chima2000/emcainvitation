<?php

namespace Database\Seeders;

use App\MessageChannel;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Seed the launch invitation packages from the company pricing plan.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Family Starter',
                'summary' => 'Birthdays, kitchen parties, and family gatherings up to 100 guests.',
                'guest_limit' => 100,
                'price' => 120000,
                'reminder_rounds' => 0,
                'includes_thanks' => false,
                'includes_pledges' => false,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Wedding Standard',
                'summary' => 'The core wedding and sendoff plan for 200 guests.',
                'guest_limit' => 200,
                'price' => 260000,
                'reminder_rounds' => 1,
                'includes_thanks' => false,
                'includes_pledges' => false,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Wedding Plus',
                'summary' => '300 guests with two reminder rounds, thank-yous, and the pledge desk.',
                'guest_limit' => 300,
                'price' => 390000,
                'reminder_rounds' => 2,
                'includes_thanks' => true,
                'includes_pledges' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            Package::query()->updateOrCreate(
                ['name' => $package['name']],
                [...$package, 'channel' => MessageChannel::Whatsapp, 'is_active' => true],
            );
        }
    }
}
