<?php

namespace App\Models;

use App\CardFont;
use App\CardLayout;
use App\Casts\EventCategorySlug;
use Database\Factories\InvitationTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'category',
    'price',
    'host_only',
    'paper',
    'ink',
    'accent',
    'font',
    'layout',
    'background_path',
    'portrait',
    'default_headline',
    'default_message',
    'motif',
    'description',
])]
class InvitationTemplate extends Model
{
    /** @use HasFactory<InvitationTemplateFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'font' => 'playfair',
        'layout' => 'classic',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => EventCategorySlug::class,
            'price' => 'integer',
            'host_only' => 'boolean',
            'font' => CardFont::class,
            'layout' => CardLayout::class,
            'portrait' => 'array',
        ];
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function shownHeadline(): ?string
    {
        return filled($this->default_headline) ? __($this->default_headline) : null;
    }

    public function shownMessage(): ?string
    {
        return filled($this->default_message) ? __($this->default_message) : null;
    }

    /**
     * @return array{kicker: string, paper: string, ink: string, accent: string, fontFamily: string, layout: string, backgroundUrl: ?string, portraitUrl: ?string, portraitSlot: ?array, scene: ?string, headline: ?string, message: ?string}
     */
    public function previewPayload(): array
    {
        return [
            'kicker' => $this->category?->label() ?? '',
            'paper' => $this->paper,
            'ink' => $this->ink,
            'accent' => $this->accent,
            'fontFamily' => $this->font->family(),
            'layout' => $this->layout->value,
            'backgroundUrl' => $this->backgroundUrl(),
            'portraitUrl' => $this->portraitUrl(),
            'portraitSlot' => $this->portraitSlot(),
            'scene' => $this->scene(),
            'headline' => $this->shownHeadline(),
            'message' => $this->shownMessage(),
        ];
    }

    public function backgroundUrl(): ?string
    {
        return self::imageUrl($this->background_path);
    }

    public function portraitUrl(): ?string
    {
        $path = is_array($this->portrait) ? ($this->portrait['path'] ?? null) : null;

        return self::imageUrl(is_string($path) ? $path : null);
    }

    /**
     * @return array{shape: string, box: array{top: string, left: string, width: string, height: string, radius: string, copy: string}}|null
     */
    public function portraitSlot(): ?array
    {
        $shape = is_array($this->portrait) ? ($this->portrait['shape'] ?? null) : null;

        if (! in_array($shape, ['arch', 'circle', 'frame'], true)) {
            return null;
        }

        return [
            'shape' => $shape,
            'box' => $this->portraitBox(),
        ];
    }

    /**
     * Where the photo sits inside this frame, measured against the card width.
     *
     * @return array{top: string, left: string, width: string, height: string, radius: string, copy: string, clip: string}
     */
    public function portraitBox(): array
    {
        $file = basename((string) $this->background_path);

        return match ($file) {
            'photo-arch-palm.jpg' => $this->photoBox('10cqi', '27%', '46%', '52cqi', '50% 50% 0 0 / 78% 78% 0 0', '66cqi', 'none'),
            'photo-arch-henna.jpg' => $this->photoBox('14cqi', '22%', '56%', '50cqi', '50% 50% 0 0 / 72% 72% 0 0', '68cqi', 'none'),
            'photo-arch-olive.jpg' => $this->photoBox('8cqi', '20%', '60%', '52cqi', '50% 50% 0 0 / 74% 74% 0 0', '64cqi', 'none'),
            'photo-circle-confetti.jpg', 'photo-circle-canopy.jpg' => $this->photoBox('8cqi', '24%', '52%', '52cqi', '50%', '64cqi', 'none'),
            'photo-frame-candle.jpg' => $this->photoBox('22cqi', '16%', '68%', '36cqi', '0.15rem', '64cqi', 'none'),
            'scene-medallion.jpg' => $this->photoBox('17%', '27%', '46%', '33%', '50%', '0', 'none'),
            'scene-polka.jpg' => $this->photoBox('8%', '30%', '40%', '28%', '50%', '0', 'none'),
            'scene-wash.jpg' => $this->photoBox('8%', '27%', '46%', '32%', '50%', '0', 'none'),
            'scene-baby.jpg' => $this->photoBox('7%', '28%', '44%', '30%', '50%', '0', 'none'),
            'scene-chalk.jpg' => $this->photoBox('29%', '23%', '54%', '34%', '0.15rem', '0', 'none'),
            'scene-torn.jpg' => $this->photoBox('0', '0', '100%', '42%', '0', '0', 'none'),
            'scene-gifts.jpg' => $this->photoBox('9%', '28%', '44%', '30%', '50%', '0', 'none'),
            'scene-split.jpg' => $this->photoBox('0', '46%', '54%', '100%', '0', '0', 'none'),
            'scene-mandap.jpg' => $this->photoBox('22%', '20%', '60%', '40%', '0.2rem', '0', 'none'),
            'scene-shore.jpg' => $this->photoBox('0', '0', '100%', '100%', '0', '0', 'none'),
            default => $this->photoBox('22cqi', '14%', '72%', '44cqi', '0.12rem', '70cqi', 'none'),
        };
    }

    /**
     * Sample names and a real date, so a photo card looks finished in the gallery.
     *
     * @return array{title: string, hosts: string, guest: string, when: string, venue: string}|null
     */
    public function galleryFace(): ?array
    {
        if ($this->portraitSlot() === null && $this->scene() === null) {
            return null;
        }

        $when = __('12 December 2026');
        $venue = __('Dar es Salaam');
        $guest = __('Neema Mushi');

        $face = match ($this->category?->slug) {
            'birthday' => ['title' => __('Neema'), 'hosts' => __('The Lyimo family'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
            'sendoff' => ['title' => __('Neema'), 'hosts' => __('Her friends'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
            'kitchen_party' => ['title' => __('Amina'), 'hosts' => __('Kitchen party friends'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
            'corporate', 'contribution' => ['title' => __('Annual meeting'), 'hosts' => __('Jumuika'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
            'gala_dinner' => ['title' => __('Amina & Juma'), 'hosts' => __('A celebration evening'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
            'anniversary' => ['title' => __('Amina & Juma'), 'hosts' => __('Years together'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
            'save_the_date' => ['title' => __('Amina & Juma'), 'hosts' => __('We are saving the date'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
            'other' => ['title' => __('Neema'), 'hosts' => __('Welcome'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
            default => ['title' => __('Amina & Juma'), 'hosts' => __('Our families'), 'guest' => $guest, 'when' => $when, 'venue' => $venue],
        };

        return match ($this->scene()) {
            'rosette' => array_replace($face, ['title' => __('Gala')]),
            'wine' => array_replace($face, ['title' => __('Wine')]),
            'candy', 'bauble', 'trees', 'fir' => array_replace($face, ['title' => __('Christmas')]),
            'shore' => array_replace($face, ['title' => __('Save the date')]),
            default => $face,
        };
    }

    /**
     * @return array{top: string, left: string, width: string, height: string, radius: string, copy: string, clip: string}
     */
    private function photoBox(string $top, string $left, string $width, string $height, string $radius, string $copy, string $clip): array
    {
        return [
            'top' => $top,
            'left' => $left,
            'width' => $width,
            'height' => $height,
            'radius' => $radius,
            'copy' => $copy,
            'clip' => $clip,
        ];
    }

    /**
     * Poster artwork is named scene-{name}.jpg. The card wording stays in the live fields.
     */
    public function scene(): ?string
    {
        $file = basename((string) $this->background_path);

        if (preg_match('/^scene-([a-z0-9]+)\.jpg$/', $file, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public static function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'card-templates/')) {
            $public = public_path('images/'.$path);
            $version = is_file($public) ? filemtime($public) : null;

            return '/images/'.$path.($version ? '?v='.$version : '');
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * @return array{paper: string, ink: string, accent: string, font: CardFont, layout: CardLayout, background_url: ?string, portrait_url: ?string, portrait_slot: ?array{shape: string}}
     */
    public function design(): array
    {
        return [
            'paper' => $this->paper,
            'ink' => $this->ink,
            'accent' => $this->accent,
            'font' => $this->font ?? CardFont::Playfair,
            'layout' => $this->layout ?? CardLayout::Classic,
            'background_url' => $this->backgroundUrl(),
            'portrait_url' => $this->portraitUrl(),
            'portrait_slot' => $this->portraitSlot(),
            'scene' => $this->scene(),
        ];
    }

    /**
     * The look used when an event has no template.
     *
     * @return array{paper: string, ink: string, accent: string, font: CardFont, layout: CardLayout, background_url: ?string, portrait_url: ?string, portrait_slot: null}
     */
    public static function defaultDesign(): array
    {
        return [
            'paper' => '#faf7f7',
            'ink' => '#16171a',
            'accent' => '#940000',
            'font' => CardFont::Playfair,
            'layout' => CardLayout::Classic,
            'background_url' => null,
            'portrait_url' => null,
            'portrait_slot' => null,
            'scene' => null,
        ];
    }

    /**
     * @param  Builder<InvitationTemplate>  $query
     */
    /**
     * Photo cards lead the gallery. The rest stay in name order.
     *
     * @param  Builder<InvitationTemplate>  $query
     */
    public function scopeOrderedForGallery(Builder $query): void
    {
        $table = $query->getModel()->getTable();

        $query->orderByRaw(
            '(select count(*) from '.$table.' as shared_artwork where shared_artwork.background_path = '.$table.'.background_path)'
        )
            ->orderByRaw("case when background_path like '%/scene-%' then 0 when portrait is null then 2 else 1 end")
            ->orderBy('name');
    }

    /**
     * @param  Builder<InvitationTemplate>  $query
     */
    public function scopeMatchingSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
