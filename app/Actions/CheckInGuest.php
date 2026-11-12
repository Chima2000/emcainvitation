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
        if ($guest->checked_in_at !== null) {
            return [
                'checkedIn' => false,
                'guest' => $guest,
            ];
        }

        $guest->checked_in_at = now();
        $guest->save();

        return [
            'checkedIn' => true,
            'guest' => $guest,
        ];
    }
}
