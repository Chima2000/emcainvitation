<?php

namespace App\Models;

use App\MessageChannel;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'summary',
    'guest_limit',
    'price',
    'channel',
    'reminder_rounds',
    'includes_thanks',
    'includes_pledges',
    'is_featured',
    'is_active',
    'sort_order',
])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'guest_limit' => 'integer',
            'price' => 'integer',
            'channel' => MessageChannel::class,
            'reminder_rounds' => 'integer',
            'includes_thanks' => 'boolean',
            'includes_pledges' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    public function pricePerGuest(): int
    {
        return $this->guest_limit > 0 ? (int) round($this->price / $this->guest_limit) : $this->price;
    }

    /**
     * @return list<string>
     */
    public function inclusions(): array
    {
        return array_values(array_filter([
            __('Invitation card for up to :count guests', ['count' => number_format($this->guest_limit)]),
            __(':channel delivery for every guest', ['channel' => $this->channel->label()]),
            __('RSVP tracking and one-time door codes'),
            $this->reminder_rounds > 0
                ? trans_choice(':count reminder round|:count reminder rounds', $this->reminder_rounds, ['count' => $this->reminder_rounds])
                : null,
            $this->includes_thanks ? __('Thank-you message after the event') : null,
            $this->includes_pledges ? __('Pledge and contribution desk') : null,
        ]));
    }
}
