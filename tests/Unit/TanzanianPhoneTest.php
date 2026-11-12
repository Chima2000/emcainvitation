<?php

use App\PhoneInput;
use App\Support\TanzanianPhone;
use Tests\TestCase;

uses(TestCase::class);

test('a local sign-in number becomes the stored international number', function () {
    expect(TanzanianPhone::normalize('0692 586 046', PhoneInput::Local))->toBe('+255692586046');
});

test('a registration number that starts with +255 stays international', function () {
    expect(TanzanianPhone::normalize('+255 692 586 046', PhoneInput::International))->toBe('+255692586046');
});

test('registration normalizes local and country-code formats', function (string $value, string $expected) {
    expect(TanzanianPhone::normalize($value, PhoneInput::Registration))->toBe($expected);
})->with([
    'local number' => ['0692586046', '+255692586046'],
    'spaced local number' => ['0692 586 046', '+255692586046'],
    'country code without plus' => ['255692586046', '+255692586046'],
    'country code with plus' => ['+255692586046', '+255692586046'],
]);

test('a number written in the other format is rejected', function (PhoneInput $input, string $value) {
    expect(TanzanianPhone::normalize($value, $input))->toBeNull();
})->with([
    'international on sign-in' => [PhoneInput::Local, '+255692586046'],
    'digits without a leading zero' => [PhoneInput::Local, '692586046'],
    'too short locally' => [PhoneInput::Local, '069258604'],
    'too long locally' => [PhoneInput::Local, '06925860461'],
    'local number on registration' => [PhoneInput::International, '0692586046'],
    'too short local registration number' => [PhoneInput::Registration, '069258604'],
    'too long local registration number' => [PhoneInput::Registration, '06925860461'],
    'country code missing on registration number' => [PhoneInput::Registration, '692586046'],
    'too short international registration number' => [PhoneInput::Registration, '25569258604'],
    'too long international registration number' => [PhoneInput::Registration, '2556925860461'],
    'country code without a plus' => [PhoneInput::International, '255692586046'],
    'too short internationally' => [PhoneInput::International, '+25569258604'],
    'another country code' => [PhoneInput::International, '+254692586046'],
]);

test('the sign-in form of a stored number starts with 0', function () {
    expect(TanzanianPhone::toLocal('+255692586046'))->toBe('0692586046');
});
