<?php

namespace App\Models;

use App\CardFont;
use App\CardLayout;
use App\Casts\EventCategorySlug;
use App\EventStatus;
use App\MessagePurpose;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'invitation_template_id',
    'title',
    'type',
    'status',
    'host_name',
    'host_name_is_organization',
    'partner_name',
    'headline',
    'message',
    'venue',
    'event_date',
    'dress_code',
    'dress_code_colors',
    'card_design',
    'is_public',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventCategorySlug::class,
            'status' => EventStatus::class,
            'event_date' => 'datetime',
            'host_name_is_organization' => 'boolean',
            'dress_code_colors' => 'array',
            'card_design' => 'array',
            'is_public' => 'boolean',
            'package_guest_limit' => 'integer',
            'package_reminder_rounds' => 'integer',
            'package_includes_thanks' => 'boolean',
            'package_price_paid' => 'integer',
            'package_purchased_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function hasPackage(): bool
    {
        return $this->package_purchased_at !== null;
    }

    /**
     * How many messages of a purpose the purchased package still covers.
     */
    public function packageAllowanceLeft(MessagePurpose $purpose): int
    {
        if (! $this->hasPackage()) {
            return 0;
        }

        $allowance = match ($purpose) {
            MessagePurpose::Invitation => $this->package_guest_limit,
            MessagePurpose::Reminder => $this->package_guest_limit * $this->package_reminder_rounds,
            MessagePurpose::Thanks => $this->package_includes_thanks ? $this->package_guest_limit : 0,
        };

        $used = $this->messages()
            ->where('purpose', $purpose)
            ->where('covered_by_package', true)
            ->count();

        return max(0, $allowance - $used);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<InvitationTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InvitationTemplate::class, 'invitation_template_id');
    }

    /**
     * @return HasMany<Guest, $this>
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * @return HasMany<Pledge, $this>
     */
    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    /**
     * @return HasMany<BudgetItem, $this>
     */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /**
     * @return HasMany<Committee, $this>
     */
    public function committees(): HasMany
    {
        return $this->hasMany(Committee::class);
    }

    /**
     * @return HasMany<EventTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(EventTask::class);
    }

    /**
     * @return HasMany<Vendor, $this>
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * @return HasMany<OutboundMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(OutboundMessage::class);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    #[Scope]
    protected function upcoming(Builder $query): Builder
    {
        return $query
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->where('event_date', '>=', now()->startOfDay())
            ->orderBy('event_date');
    }

    public function hostsLabel(): string
    {
        if ($this->partner_name) {
            return $this->host_name.' & '.$this->partner_name;
        }

        return $this->host_name;
    }

    public function cardHeadline(): string
    {
        return $this->headline ?: ($this->template?->default_headline ?: __('You are invited'));
    }

    public function cardMessage(): ?string
    {
        return $this->message ?: $this->template?->default_message;
    }

    /**
     * The template look with the host's own changes on top.
     *
     * @return array{paper: string, ink: string, accent: string, font: CardFont, layout: CardLayout, background_url: ?string}
     */
    public function cardDesign(): array
    {
        $design = $this->template?->design() ?? InvitationTemplate::defaultDesign();
        $custom = $this->card_design ?? [];

        foreach (['paper', 'ink', 'accent'] as $color) {
            if (filled($custom[$color] ?? null)) {
                $design[$color] = $custom[$color];
            }
        }

        $design['font'] = CardFont::tryFrom((string) ($custom['font'] ?? '')) ?? $design['font'];
        $design['layout'] = CardLayout::tryFrom((string) ($custom['layout'] ?? '')) ?? $design['layout'];

        if (filled($custom['background_path'] ?? null)) {
            $design['background_url'] = Storage::disk('public')->url($custom['background_path']);
        } elseif (! empty($custom['hide_background'])) {
            $design['background_url'] = null;
        }

        return $design;
    }

    public function cardKicker(): string
    {
        return $this->template?->category?->label() ?? $this->type?->label() ?? __('Invitation');
    }

    public function guestMatchingCode(string $code): ?Guest
    {
        $code = trim($code);

        if (preg_match('#/(?:i|check-in)/([A-Za-z0-9]+)#', $code, $matches) === 1) {
            return $this->guests()->where('token', $matches[1])->first();
        }

        return $this->guests()->where('serial', strtoupper($code))->first();
    }
}
