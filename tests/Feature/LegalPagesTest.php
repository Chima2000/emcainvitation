<?php

test('the privacy policy opens in Swahili and links to the terms', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Sera ya faragha')
        ->assertSee('Taarifa tunazokusanya')
        ->assertSee('Sheria ya Ulinzi wa Taarifa Binafsi ya mwaka 2022')
        ->assertSee('Imesasishwa mwisho: 2 Novemba 2026')
        ->assertSee(config('emca.support_email'))
        ->assertSee(route('terms'), false)
        ->assertDontSee('Information we collect');
});

test('the terms and conditions open in Swahili and link to the privacy policy', function () {
    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Vigezo na masharti')
        ->assertSee('Ahadi na michango')
        ->assertSee('Sheria inayotumika')
        ->assertSee(config('emca.support_phone'))
        ->assertSee(route('privacy'), false)
        ->assertDontSee('Pledges and contributions');
});

test('both legal pages follow the saved language', function (string $routeName, string $heading) {
    $this->withCookie('locale', 'en')
        ->get(route($routeName))
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('Last updated: 2 November 2026')
        ->assertSee('Contact us');
})->with([
    'privacy policy' => ['privacy', 'Information we collect'],
    'terms and conditions' => ['terms', 'Governing law'],
]);

test('the site footer links to both legal pages', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('privacy'), false)
        ->assertSee(route('terms'), false)
        ->assertSee('Sera ya faragha')
        ->assertSee('Vigezo na masharti');
});

test('the registration form tells hosts which terms they accept', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Kufungua akaunti kunamaanisha unakubali')
        ->assertSee(route('terms'), false)
        ->assertSee(route('privacy'), false);
});
