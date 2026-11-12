<?php

namespace App;

enum GuestGender: string
{
    case Female = 'female';
    case Male = 'male';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function label(): string
    {
        return __('labels.genders.'.$this->value);
    }
}
