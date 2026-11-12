<?php

namespace App\Models;

use App\CardFont;
use App\CardLayout;
use App\Casts\EventCategorySlug;
use Database\Factories\InvitationTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'category',
    'price',
    'paper',
    'ink',
    'accent',
    'font',
    'layout',
    'background_path',
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
            'font' => CardFont::class,
            'layout' => CardLayout::class,
        ];
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function backgroundUrl(): ?string
    {
        return $this->background_path ? Storage::disk('public')->url($this->background_path) : null;
    }

    /**
     * @return array{paper: string, ink: string, accent: string, font: CardFont, layout: CardLayout, background_url: ?string}
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
        ];
    }

    /**
     * The look used when an event has no template.
     *
     * @return array{paper: string, ink: string, accent: string, font: CardFont, layout: CardLayout, background_url: ?string}
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
        ];
    }
}
