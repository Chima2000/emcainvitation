<?php

namespace App\Models;

use App\GuestGender;
use App\RsvpStatus;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'email', 'gender', 'party_size', 'rsvp_status', 'notes'])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'gender' => GuestGender::class,
            'rsvp_status' => RsvpStatus::class,
            'sent_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<Pledge, $this>
     */
    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    /**
     * @return BelongsToMany<Committee, $this>
     */
    public function committees(): BelongsToMany
    {
        return $this->belongsToMany(Committee::class);
    }

    /**
     * @return HasMany<OutboundMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(OutboundMessage::class);
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function internationalPhone(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        return $digits;
    }

    public function invitationMessage(): string
    {
        $event = $this->event;
        $when = $event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A');

        return __('Hello :name, :hosts invites you to :title on :when at :venue. Your card number is :serial. It can be used once at the door. Open your card: :url', [
            'name' => $this->name,
            'hosts' => $event->hostsLabel(),
            'title' => $event->title,
            'when' => $when,
            'venue' => $event->venue,
            'serial' => $this->serial,
            'url' => route('invitations.show', $this->token),
        ]);
    }

    public function whatsAppUrl(): ?string
    {
        $phone = $this->internationalPhone();

        if ($phone === null) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($this->invitationMessage());
    }

    public function smsUrl(): ?string
    {
        $phone = $this->internationalPhone();

        if ($phone === null) {
            return null;
        }

        return 'sms:+'.$phone.'?&body='.rawurlencode($this->invitationMessage());
    }
}
