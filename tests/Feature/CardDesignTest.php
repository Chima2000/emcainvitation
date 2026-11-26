<?php

use App\CardFont;
use App\CardLayout;
use App\Models\Event;
use App\Models\Guest;
use App\Models\InvitationTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function harusiGoldTemplate(): InvitationTemplate
{
    return InvitationTemplate::factory()->create([
        'name' => 'Harusi Gold',
        'paper' => '#fffaf0',
        'ink' => '#1a1a1a',
        'accent' => '#b8860b',
        'font' => CardFont::Playfair,
        'layout' => CardLayout::Elegant,
        'default_headline' => 'Mnakaribishwa kwa furaha',
        'default_message' => 'Tafadhali thibitisha mapema.',
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function cardDesignForm(InvitationTemplate $template, array $overrides = []): array
{
    return [
        'invitation_template_id' => $template->id,
        'paper' => $template->paper,
        'ink' => $template->ink,
        'accent' => $template->accent,
        'font' => $template->font->value,
        'layout' => $template->layout->value,
        'headline' => '',
        'message' => '',
        'dress_code' => '',
        ...$overrides,
    ];
}

test('an event on a template shows the template look and default wording to guests', function () {
    $template = harusiGoldTemplate();
    $event = Event::factory()->for(User::factory())->create(['invitation_template_id' => $template->id, 'headline' => null, 'message' => null]);
    $guest = Guest::factory()->for($event)->create();

    $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('jp-card--elegant', false)
        ->assertSee('--jp-accent: #b8860b', false)
        ->assertSee('Mnakaribishwa kwa furaha')
        ->assertSee('Tafadhali thibitisha mapema.');
});

test('a catalog headline is shown in the page language until the host writes their own', function () {
    $template = InvitationTemplate::factory()->create([
        'default_headline' => 'Together with our families',
        'default_message' => 'Join us as we celebrate our wedding. We look forward to sharing this special day with you.',
    ]);
    $event = Event::factory()->for(User::factory())->create([
        'invitation_template_id' => $template->id,
        'headline' => null,
        'message' => null,
    ]);
    $guest = Guest::factory()->for($event)->create();

    $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('Pamoja na familia zetu')
        ->assertSee('Jiunge nasi tunapoadhimisha harusi yetu. Tunatazamia kushiriki siku hii maalum nawe.');

    $event->update([
        'headline' => 'Karibu kwenye harusi',
        'message' => 'Tafadhali fika mapema sana',
    ]);

    $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('Karibu kwenye harusi')
        ->assertSee('Tafadhali fika mapema sana')
        ->assertDontSee('Pamoja na familia zetu');
});

test('a host customises colours, font, layout, picture and words on top of a template', function () {
    Storage::fake('public');
    $template = harusiGoldTemplate();
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create(['invitation_template_id' => $template->id]);
    $guest = Guest::factory()->for($event)->create();

    $this->actingAs($host)
        ->get(route('events.design.edit', $event))
        ->assertOk()
        ->assertSee('Harusi Gold')
        ->assertSee(__('Design in Canva'))
        ->assertSee(__('Open Canva'))
        ->assertDontSee(__('View more'))
        ->assertSee('name="font"', false)
        ->assertSee('name="background"', false);

    $this->actingAs($host)
        ->put(route('events.design.update', $event), cardDesignForm($template, [
            'accent' => '#2E7D32',
            'font' => CardFont::GreatVibes->value,
            'layout' => CardLayout::Modern->value,
            'headline' => 'Karibu kwenye harusi yetu',
            'message' => 'Mavazi ya kijani.',
            'background' => UploadedFile::fake()->image('couple.jpg', 800, 1100),
        ]))
        ->assertRedirect(route('events.design.edit', $event))
        ->assertSessionHasNoErrors();

    $event->refresh();
    $backgroundPath = $event->card_design['background_path'];

    expect($event->card_design)->toMatchArray([
        'accent' => '#2e7d32',
        'font' => CardFont::GreatVibes->value,
        'layout' => CardLayout::Modern->value,
    ])
        ->and($event->card_design)->not->toHaveKeys(['paper', 'ink'])
        ->and($event->headline)->toBe('Karibu kwenye harusi yetu');

    Storage::disk('public')->assertExists($backgroundPath);

    $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('jp-card--modern', false)
        ->assertSee('--jp-accent: #2e7d32', false)
        ->assertSee('--jp-paper: #fffaf0', false)
        ->assertSee($backgroundPath, false)
        ->assertSee('Karibu kwenye harusi yetu')
        ->assertSee('Mavazi ya kijani.');

    expect($template->fresh()->accent)->toBe('#b8860b');
});

test('the template stays in charge when the host keeps its values', function () {
    $template = harusiGoldTemplate();
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create(['card_design' => ['accent' => '#000000']]);

    $this->actingAs($host)
        ->put(route('events.design.update', $event), cardDesignForm($template, ['accent' => '#B8860B']))
        ->assertSessionHasNoErrors();

    $event->refresh();

    expect($event->card_design)->toBeNull()
        ->and($event->invitation_template_id)->toBe($template->id);

    $template->update(['accent' => '#123456']);

    expect($event->fresh()->cardDesign()['accent'])->toBe('#123456');
});

test('a host removes their own picture and the file is deleted', function () {
    Storage::fake('public');
    $template = harusiGoldTemplate();
    $host = User::factory()->create();
    $path = UploadedFile::fake()->image('old.jpg')->store('card-backgrounds/events', 'public');
    $event = Event::factory()->for($host)->create(['invitation_template_id' => $template->id, 'card_design' => ['background_path' => $path]]);

    $this->actingAs($host)
        ->put(route('events.design.update', $event), cardDesignForm($template, ['remove_background' => '1']))
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($path);
    expect($event->fresh()->card_design)->toBeNull();
});

test('a host photo replaces the sample and stays in the same frame', function () {
    Storage::fake('public');
    $template = InvitationTemplate::factory()->create([
        'name' => 'Couple Arch',
        'portrait' => [
            'path' => 'card-templates/portraits/couple-formal.jpg',
            'shape' => 'arch',
        ],
    ]);
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create(['invitation_template_id' => $template->id]);
    $guest = Guest::factory()->for($event)->create();

    $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('jp-card__photo--arch', false)
        ->assertSee('card-templates/portraits/couple-formal.jpg', false);

    $this->actingAs($host)
        ->put(route('events.design.update', $event), cardDesignForm($template, [
            'portrait' => UploadedFile::fake()->image('ours.jpg'),
        ]))
        ->assertSessionHasNoErrors();

    $path = $event->fresh()->card_design['portrait_path'];

    expect($path)->toStartWith('card-portraits/events/');
    Storage::disk('public')->assertExists($path);

    $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('jp-card__photo--arch', false)
        ->assertSee($path, false)
        ->assertDontSee('couple-formal.jpg', false);
});

test('the design form rejects invalid colours and fonts', function () {
    $template = harusiGoldTemplate();
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->put(route('events.design.update', $event), cardDesignForm($template, ['paper' => 'red', 'font' => 'comic-sans']))
        ->assertSessionHasErrors(['paper', 'font']);
});

test('a host cannot design another host\'s card', function () {
    $template = harusiGoldTemplate();
    $event = Event::factory()->for(User::factory())->create();

    $this->actingAs(User::factory()->create())
        ->get(route('events.design.edit', $event))
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->put(route('events.design.update', $event), cardDesignForm($template, ['accent' => '#000000']))
        ->assertNotFound();

    expect($event->fresh()->card_design)->toBeNull();
});

test('a super admin creates a template with font, layout, picture and default wording', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => 'Kanga Modern',
            'category' => 'wedding',
            'price' => 0,
            'paper' => '#FAF7F7',
            'ink' => '#16171A',
            'accent' => '#940000',
            'font' => CardFont::Montserrat->value,
            'layout' => CardLayout::Minimal->value,
            'default_headline' => 'Tunawaalika',
            'default_message' => 'Karibuni sana.',
            'background' => UploadedFile::fake()->image('kanga.png', 600, 900),
            'motif' => 'Kanga border',
            'description' => 'Clean modern card.',
        ])
        ->assertSessionHasNoErrors();

    $template = InvitationTemplate::query()->where('name', 'Kanga Modern')->sole();

    expect($template->font)->toBe(CardFont::Montserrat)
        ->and($template->layout)->toBe(CardLayout::Minimal)
        ->and($template->default_headline)->toBe('Tunawaalika');

    Storage::disk('public')->assertExists($template->background_path);

    $this->actingAs($admin)
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertSee('jp-card--minimal', false)
        ->assertSee($template->background_path, false);

    $this->actingAs($admin)->delete(route('admin.templates.destroy', $template));

    Storage::disk('public')->assertMissing($template->background_path);
});

test('the design page shows six templates and keeps the rest behind view more', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create([
        'invitation_template_id' => InvitationTemplate::factory()->create(['name' => 'Card 08'])->id,
    ]);

    foreach (range(1, 7) as $number) {
        InvitationTemplate::factory()->create(['name' => 'Card 0'.$number]);
    }

    $this->actingAs($host)
        ->get(route('events.design.edit', $event))
        ->assertOk()
        ->assertSee(__('View more'))
        ->assertSeeInOrder(['Card 08', 'id="design-template-more" hidden', 'Card 06'], false);
});

test('the public templates page shows six cards and hides the rest', function () {
    foreach (range(1, 8) as $number) {
        InvitationTemplate::factory()->create(['name' => 'Card 0'.$number]);
    }

    $this->get(route('templates.index'))
        ->assertOk()
        ->assertSee(__('Design in Canva'))
        ->assertSee('emca-canva-action', false)
        ->assertSee(route('login', ['next' => 'templates']), false)
        ->assertSee(__('Open Canva'))
        ->assertSee('emca-template-fold', false)
        ->assertSee(__('View more'))
        ->assertSeeInOrder(['Card 06', 'data-fold-item hidden', 'Card 07'], false);
});

test('open canva on the public templates page continues to the template library after sign-in', function () {
    User::factory()->create([
        'phone' => '+255712345678',
        'password' => 'Secret@12',
    ]);

    $this->get(route('login', ['next' => 'templates']))->assertOk();

    $this->post(route('login'), [
        'phone' => '0712345678',
        'password' => 'Secret@12',
    ])->assertRedirect(route('template-library.index'));
});

test('a new account from open canva reaches the template library after the profile', function () {
    User::factory()->create([
        'phone' => '+255787000111',
        'password' => 'Secret@12',
        'profile_completed_at' => null,
    ]);

    $this->get(route('login', ['next' => 'templates']))->assertOk();

    $this->post(route('login'), [
        'phone' => '0787000111',
        'password' => 'Secret@12',
    ])->assertRedirect(route('profile.edit'));

    $this->put(route('profile.update'), [
        'country' => 'Tanzania',
        'region' => 'Arusha',
        'district' => 'Meru District',
        'ward' => 'Akheri',
        'street' => 'Nkoaranga Road',
        'address_line' => 'Near the market',
    ])->assertRedirect(route('template-library.index'));
});

test('a visitor who picks a public card starts the next event with that card', function () {
    $template = InvitationTemplate::factory()->create(['name' => 'Chosen Card']);
    User::factory()->create([
        'phone' => '+255712345678',
        'password' => 'Secret@12',
    ]);

    $this->get(route('templates.index'))
        ->assertOk()
        ->assertSee(route('register', ['template' => $template->id]), false);

    $this->get(route('register', ['template' => $template->id]))
        ->assertOk()
        ->assertSee(route('login', ['template' => $template->id]), false);

    $this->post(route('login'), [
        'phone' => '0712345678',
        'password' => 'Secret@12',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('events.index', ['new' => 1]))
        ->assertOk()
        ->assertSee('value="Chosen Card"', false);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertDontSee('value="Chosen Card"', false);
});

test('a new host keeps the chosen public card through location details', function () {
    $template = InvitationTemplate::factory()->create(['name' => 'Chosen Card']);

    $this->get(route('register', ['template' => $template->id]));

    $this->post(route('register'), [
        'first_name' => 'Amina',
        'last_name' => 'Mwangi',
        'phone' => '+255692586046',
        'email' => 'amina@example.com',
        'password' => 'Secret@12',
        'password_confirmation' => 'Secret@12',
    ])->assertRedirect(route('profile.edit'));

    $this->put(route('profile.update'), [
        'country' => 'Tanzania',
        'region' => 'Arusha',
        'district' => 'Meru District',
        'ward' => 'Akheri',
        'street' => 'Nkoaranga Road',
        'address_line' => 'Near the market',
    ])->assertRedirect(route('events.index', ['new' => 1, 'template' => $template->id]));
});

test('an unknown public card does not change where sign-in goes', function () {
    User::factory()->create([
        'phone' => '+255712345678',
        'password' => 'Secret@12',
    ]);

    $this->get(route('register', ['template' => 999999]));

    $this->post(route('login'), [
        'phone' => '0712345678',
        'password' => 'Secret@12',
    ])->assertRedirect(route('dashboard'));
});

test('the wallet top-up form shows the real payment logos', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('wallet.index'))
        ->assertOk()
        ->assertSee('images/payments/mpesa.svg', false)
        ->assertSee('images/payments/airtel-money.png', false)
        ->assertSee('images/payments/mixx-by-yas.png', false)
        ->assertSee('images/payments/halopesa.png', false);
});
