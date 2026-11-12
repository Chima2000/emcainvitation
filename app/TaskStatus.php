<?php

namespace App;

enum TaskStatus: string
{
    case Open = 'open';
    case Done = 'done';

    public function label(): string
    {
        return __('labels.tasks.'.$this->value);
    }
}
