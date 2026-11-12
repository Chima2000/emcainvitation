<?php

namespace App;

enum CardFont: string
{
    case Playfair = 'playfair';
    case Cormorant = 'cormorant';
    case Lora = 'lora';
    case GreatVibes = 'great_vibes';
    case DancingScript = 'dancing_script';
    case Montserrat = 'montserrat';

    public function label(): string
    {
        return match ($this) {
            self::Playfair => 'Playfair Display',
            self::Cormorant => 'Cormorant Garamond',
            self::Lora => 'Lora',
            self::GreatVibes => 'Great Vibes',
            self::DancingScript => 'Dancing Script',
            self::Montserrat => 'Montserrat',
        };
    }

    public function family(): string
    {
        return match ($this) {
            self::Playfair => "'Playfair Display', Georgia, serif",
            self::Cormorant => "'Cormorant Garamond', Georgia, serif",
            self::Lora => "'Lora', Georgia, serif",
            self::GreatVibes => "'Great Vibes', 'Brush Script MT', cursive",
            self::DancingScript => "'Dancing Script', 'Brush Script MT', cursive",
            self::Montserrat => "'Montserrat', 'Helvetica Neue', Arial, sans-serif",
        };
    }

    public static function stylesheetUrl(): string
    {
        return 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;700&family=Dancing+Script:wght@500;700&family=Great+Vibes&family=Lora:wght@400;600&family=Montserrat:wght@400;600;700&family=Playfair+Display:wght@500;700&display=swap';
    }
}
