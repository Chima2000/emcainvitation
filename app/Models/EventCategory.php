<?php

namespace App\Models;

use App\Support\EventCategoryRegistry;
use Database\Factories\EventCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['slug', 'name', 'name_sw', 'icon', 'photo', 'photo_path', 'sort_order', 'is_active'])]
class EventCategory extends Model
{
    /** @use HasFactory<EventCategoryFactory> */
    use HasFactory;

    public const string DefaultIcon = 'fa-calendar-days';

    public const string DefaultPhoto = 'celebration.jpg';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'photo' => self::DefaultPhoto,
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(EventCategoryRegistry::class)->flush());
        static::deleted(fn () => app(EventCategoryRegistry::class)->flush());
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'type', 'slug');
    }

    /**
     * @return HasMany<InvitationTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(InvitationTemplate::class, 'category', 'slug');
    }

    public static function findBySlug(?string $slug): ?self
    {
        return app(EventCategoryRegistry::class)->find($slug);
    }

    /**
     * Switched-on categories in display order, for pickers and filters.
     *
     * @return Collection<int, EventCategory>
     */
    public static function options(): Collection
    {
        return app(EventCategoryRegistry::class)->active();
    }

    /**
     * Every category, including switched-off ones, in display order.
     *
     * @return Collection<int, EventCategory>
     */
    public static function catalog(): Collection
    {
        return app(EventCategoryRegistry::class)->all()->values();
    }

    /**
     * Stand-in for a slug whose category row was removed, so old records still render.
     */
    public static function placeholder(string $slug): self
    {
        return new self(['slug' => $slug, 'name' => Str::headline($slug)]);
    }

    /**
     * Stock photos shipped in public/images/occasions and public/images/page-titles/occasions.
     *
     * @return array<string, string>
     */
    public static function stockPhotos(): array
    {
        return [
            'wedding.jpg' => __('Wedding'),
            'sendoff.jpg' => __('Sendoff'),
            'gathering.jpg' => __('Family gathering'),
            'birthday.jpg' => __('Birthday'),
            'corporate.jpg' => __('Corporate'),
            'celebration.jpg' => __('Celebration'),
        ];
    }

    /**
     * Font Awesome icons offered when an admin picks a category icon; each exists in both the admin and public icon sets.
     *
     * @return list<string>
     */
    public static function iconChoices(): array
    {
        return [
            'fa-ring', 'fa-moon', 'fa-champagne-glasses', 'fa-gift', 'fa-cake-candles', 'fa-utensils',
            'fa-heart', 'fa-calendar-check', 'fa-calendar-days', 'fa-hand-holding-dollar', 'fa-briefcase',
            'fa-graduation-cap', 'fa-church', 'fa-mosque', 'fa-hands-praying', 'fa-baby', 'fa-music',
            'fa-microphone', 'fa-masks-theater', 'fa-award', 'fa-crown', 'fa-gem', 'fa-dove',
            'fa-handshake', 'fa-hand-holding-heart', 'fa-martini-glass-citrus', 'fa-futbol', 'fa-star',
        ];
    }

    public function label(): string
    {
        if (app()->getLocale() === 'sw' && filled($this->name_sw)) {
            return $this->name_sw;
        }

        return $this->name;
    }

    public function iconClass(): string
    {
        return $this->icon ?: self::DefaultIcon;
    }

    /**
     * Wide pictures are used for page headers, regular ones for cards.
     */
    public function photoUrl(bool $wide = false): string
    {
        if (filled($this->photo_path)) {
            return Storage::disk('public')->url($this->photo_path);
        }

        $photo = array_key_exists((string) $this->photo, self::stockPhotos()) ? $this->photo : self::DefaultPhoto;

        return asset(($wide ? 'images/page-titles/occasions/' : 'images/occasions/').$photo);
    }

    public function isInUse(): bool
    {
        return $this->events()->exists()
            || $this->templates()->exists()
            || DemoRequest::query()->where('event_type', $this->slug)->exists();
    }
}
