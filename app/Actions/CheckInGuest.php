<?php

namespace App\Actions;

use App\Models\Guest;

class CheckInGuest
{
    /**
     * Mark a guest present the first time their card is presented.
     *
     * @return array{checkedIn: bool, guest: Guest}
     */
    public function handle(Guest $guest): array
    {
        $updated = Guest::query()
            ->whereKey($guest->getKey())
            ->whereNull('checked_in_at')
            ->update(['checked_in_at' => now()]);

        $guest->refresh();

        return [
            'checkedIn' => $updated === 1,
            'guest' => $guest,
        ];
    }
}
