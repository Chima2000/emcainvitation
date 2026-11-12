<?php

namespace App;

enum PhoneInput: string
{
    case Local = 'local';
    case International = 'international';
    case Registration = 'registration';
}
