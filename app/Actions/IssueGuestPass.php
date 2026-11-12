<?php

namespace App\Actions;

use App\GuestGender;
use App\Models\Event;
use App\Models\Guest;
use App\RsvpStatus;
use Illuminate\Support\Str;

class IssueGuestPass
{
    /**
     * @param  array{name: string, phone?: ?string, email?: ?string, gender?: GuestGender|string|null, party_size?: int, rsvp_status?: RsvpStatus|string, notes?: ?string}  $attributes
     */
    public function handle(Event $event, array $attributes): Guest
    {
        $guest = $event->guests()->make([
            'name' => $attributes['name'],
            'phone' => $attributes['phone'] ?? null,
            'email' => $attributes['email'] ?? null,
            'gender' => $attributes['gender'] ?? null,
            'party_size' => $attributes['party_size'] ?? 1,
            'rsvp_status' => $attributes['rsvp_status'] ?? RsvpStatus::Pending,
            'notes' => $attributes['notes'] ?? null,
        ]);

        $guest->serial = $this->uniqueSerial();
        $guest->token = Str::random(40);
        $guest->save();

        return $guest;
    }

    private function uniqueSerial(): string
    {
        do {
            $serial = 'EM-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Guest::query()->where('serial', $serial)->exists());

        return $serial;
    }
}
