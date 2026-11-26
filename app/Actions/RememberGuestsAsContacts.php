<?php

namespace App\Actions;

use App\Models\Contact;
use App\Models\Guest;
use App\Models\User;

class RememberGuestsAsContacts
{
    /**
     * Copy guests already saved on events into each host's contact book.
     * The same phone on more than one event stays one contact, and the latest guest supplies the details.
     */
    public function handle(): void
    {
        Guest::query()
            ->with('event.user')
            ->chunkById(200, function ($guests): void {
                foreach ($guests as $guest) {
                    $owner = $guest->event?->user;

                    if (! $owner instanceof User) {
                        continue;
                    }

                    Contact::remember(
                        $owner,
                        $guest->name,
                        $guest->phone,
                        $guest->email,
                        $guest->gender?->value,
                    );
                }
            });
    }
}
