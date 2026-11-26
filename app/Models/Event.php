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
    'bride_name',
    'groom_name',
    'bride_family',
    'groom_family',
    'ceremony_place',
    'reception_venue',
    'reception_time',
    'arrive_by',
    'dress_code_colors',
    'card_design',
    'is_public',
    'auto_reminder',
    'reminder_days',
    'auto_reminder_sent_at',
])]
class Event extends Model
{
    public const int TrialGuestLimit = 10;

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
            'auto_reminder' => 'boolean',
            'reminder_days' => 'integer',
            'auto_reminder_sent_at' => 'datetime',
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

    public function trialGuestsRemaining(): ?int
    {
        if (! $this->user?->isLead()) {
            return null;
        }

        $listed = array_key_exists('guests_count', $this->getAttributes())
            ? (int) $this->guests_count
            : $this->guests()->count();

        return max(0, self::TrialGuestLimit - $listed);
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
            MessagePurpose::Pledge => 0,
            MessagePurpose::Trial => 0,
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
        return $this->headline ?: ($this->template?->shownHeadline() ?: __('You are invited'));
    }

    public function cardMessage(): ?string
    {
        return $this->message ?: $this->template?->shownMessage();
    }

    /**
     * Extra lines a wedding card can print in the details it already shows.
     *
     * @return list<string>
     */
    public function weddingCardLines(): array
    {
        if ($this->type?->slug !== 'wedding') {
            return [];
        }

        $lines = [];
        $couple = trim(implode(' & ', array_filter([$this->bride_name, $this->groom_name])));

        if ($couple !== '') {
            $lines[] = $couple;
        }

        if (filled($this->bride_family)) {
            $lines[] = __('Bride’s family: :name', ['name' => $this->bride_family]);
        }

        if (filled($this->groom_family)) {
            $lines[] = __('Groom’s family: :name', ['name' => $this->groom_family]);
        }

        if (filled($this->ceremony_place)) {
            $lines[] = __('Vows at :place', ['place' => $this->ceremony_place]);
        }

        if (filled($this->reception_venue)) {
            $lines[] = __('Reception at :place', ['place' => $this->reception_venue]);
        }

        if (filled($this->reception_time)) {
            $lines[] = __('Reception time: :time', ['time' => $this->reception_time]);
        }

        if (filled($this->arrive_by)) {
            $lines[] = __('Arrive by :time', ['time' => $this->arrive_by]);
        }

        return $lines;
    }

    /**
     * @return HasMany<GuestGroup, $this>
     */
    public function guestGroups(): HasMany
    {
        return $this->hasMany(GuestGroup::class)->orderBy('name');
    }

    /**
     * The template look with the host's own changes on top.
     *
     * @return array{paper: string, ink: string, accent: string, font: CardFont, layout: CardLayout, background_url: ?string, portrait_url: ?string, portrait_slot: ?array{shape: string}}
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
            $design['background_url'] = '/storage/'.ltrim(str_replace('\\', '/', $custom['background_path']), '/');
        } elseif (! empty($custom['hide_background'])) {
            $design['background_url'] = null;
        }

        $usingTemplateArt = ! filled($custom['background_path'] ?? null) && empty($custom['hide_background']);

        if (! $usingTemplateArt) {
            $design['portrait_url'] = null;
            $design['portrait_slot'] = null;
            $design['scene'] = null;
        } elseif (filled($custom['portrait_path'] ?? null)) {
            $design['portrait_url'] = '/storage/'.ltrim(str_replace('\\', '/', $custom['portrait_path']), '/');
        }

        return $design;
    }

    /**
     * Colours for this event's box on the events list.
     *
     * Host edits sit on top of the chosen template. Events with no card yet use the default palette.
     *
     * @return array{paper: string, ink: string, accent: string}
     */
    public function listCardPalette(): array
    {
        $design = $this->cardDesign();
        $fallback = InvitationTemplate::defaultDesign();
        $palette = [];

        foreach (['paper', 'ink', 'accent'] as $color) {
            $palette[$color] = self::hexColor($design[$color] ?? null) ?? $fallback[$color];
        }

        return $palette;
    }

    private static function hexColor(mixed $color): ?string
    {
        if (! is_string($color) || preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) {
            return null;
        }

        return $color;
    }

    public function cardKicker(): string
    {
        return $this->template?->category?->label() ?? $this->type?->label() ?? __('Invitation');
    }

    public function guestMatchingCode(string $code): ?Guest
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $token = $this->passToken($code);

        if ($token !== null) {
            return $this->soleGuest('token', $token);
        }

        return $this->soleGuest('serial', strtoupper($code));
    }

    /**
     * Admit only when one guest on this event owns the exact card number or code.
     */
    private function soleGuest(string $column, string $value): ?Guest
    {
        $guests = $this->guests()->where($column, $value)->limit(2)->get();

        if ($guests->count() !== 1) {
            return null;
        }

        return $guests->first();
    }

    /**
     * The invitation or door link is the code. Anything else is a card number.
     */
    private function passToken(string $code): ?string
    {
        $path = parse_url($code, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        if (preg_match('#^/(?:i|check-in)/([A-Za-z0-9]{20,80})/?$#', $path, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
