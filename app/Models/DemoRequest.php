<?php

namespace App\Models;

use App\Casts\EventCategorySlug;
use Database\Factories\DemoRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'email', 'phone', 'organization', 'event_type', 'guest_count', 'message'])]
class DemoRequest extends Model
{
    /** @use HasFactory<DemoRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => EventCategorySlug::class,
            'guest_count' => 'integer',
            'trial_starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'trial_granted_at' => 'datetime',
            'trial_cancelled_at' => 'datetime',
            'must_set_password' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trialIsActive(): bool
    {
        return $this->trial_cancelled_at === null
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }
}
