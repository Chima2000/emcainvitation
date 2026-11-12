<?php

namespace App;

enum CardLayout: string
{
    case Classic = 'classic';
    case Elegant = 'elegant';
    case Modern = 'modern';
    case Minimal = 'minimal';

    public function label(): string
    {
        return __('labels.card_layouts.'.$this->value);
    }

    public function description(): string
    {
        return __('labels.card_layout_descriptions.'.$this->value);
    }
}
