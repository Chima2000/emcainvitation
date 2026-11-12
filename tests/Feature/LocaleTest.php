<?php

test('the home page opens in Swahili', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('favicon.svg', false)
        ->assertSee('favicon.ico', false)
        ->assertSee('apple-touch-icon.png', false)
        ->assertDontSee('admin/assets/images/favicon.png', false)
        ->assertSee('Tuma kadi.')
        ->assertSee('Kiswahili')
        ->assertSee('English');
});

test('a visitor can switch the site to English and back to Swahili', function () {
    $this->from(route('home'))
        ->post(route('locale.update', 'en'))
        ->assertRedirect(route('home'))
        ->assertCookie('locale', 'en');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Send the card.')
        ->assertDontSee('Tuma kadi.');

    $this->from(route('home'))
        ->post(route('locale.update', 'sw'))
        ->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Tuma kadi.');
});

test('an unknown language is rejected', function () {
    $this->post(route('locale.update', 'fr'))
        ->assertNotFound();
});

test('a saved language is used on the next visit', function () {
    $this->withCookie('locale', 'en')
        ->get(route('features'))
        ->assertOk()
        ->assertSee('See pricing')
        ->assertDontSee('Tazama bei');
});
