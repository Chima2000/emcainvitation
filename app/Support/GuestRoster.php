<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Guest;

final class GuestRoster
{
    /**
     * @var array<string, string>
     */
    private array $phones = [];

    /**
     * @var array<string, true>
     */
    private array $emails = [];

    /**
     * @var array<string, true>
     */
    private array $namesWithoutContact = [];

    public static function forEvent(Event $event, ?int $exceptGuestId = null): self
    {
        $roster = new self;

        $event->guests()
            ->when($exceptGuestId !== null, fn ($query) => $query->whereKeyNot($exceptGuestId))
            ->get(['name', 'phone', 'email'])
            ->each(fn (Guest $guest) => $roster->remember($guest->name, $guest->phone, $guest->email));

        return $roster;
    }

    /**
     * @return list<'name'|'phone'|'email'>
     */
    public function conflicts(?string $name, ?string $phone, ?string $email): array
    {
        $conflicts = [];
        $phoneKey = Guest::phoneKey($phone);
        $emailKey = self::emailKey($email);

        if ($phoneKey !== null && isset($this->phones[$phoneKey])) {
            $conflicts[] = 'phone';
        }

        if ($emailKey !== null && isset($this->emails[$emailKey])) {
            $conflicts[] = 'email';
        }

        if ($phoneKey === null && $emailKey === null && isset($this->namesWithoutContact[self::nameKey($name)])) {
            $conflicts[] = 'name';
        }

        return $conflicts;
    }

    public function phoneHolder(?string $phone): ?string
    {
        $phoneKey = Guest::phoneKey($phone);

        if ($phoneKey === null) {
            return null;
        }

        return $this->phones[$phoneKey] ?? null;
    }

    public function remember(?string $name, ?string $phone, ?string $email): void
    {
        $phoneKey = Guest::phoneKey($phone);
        $emailKey = self::emailKey($email);

        if ($phoneKey !== null) {
            $this->phones[$phoneKey] = trim((string) $name) !== '' ? trim((string) $name) : __('This guest');
        }

        if ($emailKey !== null) {
            $this->emails[$emailKey] = true;
        }

        $nameKey = self::nameKey($name);

        if ($phoneKey === null && $emailKey === null && $nameKey !== '') {
            $this->namesWithoutContact[$nameKey] = true;
        }
    }

    private static function emailKey(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : $email;
    }

    private static function nameKey(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }
}
