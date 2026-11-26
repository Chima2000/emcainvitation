<?php

use App\Models\EventCategory;
use App\Models\InvitationTemplate;
use App\Models\User;
use Database\Seeders\InvitationTemplateSeeder;

test('every event category has its own starter templates', function () {
    $this->seed(InvitationTemplateSeeder::class);
    $this->seed(InvitationTemplateSeeder::class);

    $categories = EventCategory::query()->withCount('templates')->get();

    expect($categories)->not->toBeEmpty();

    foreach ($categories as $category) {
        expect($category->templates_count)->toBeGreaterThanOrEqual(1)
            ->and(InvitationTemplate::query()
                ->where('category', $category->slug)
                ->where('name', 'like', '% - Signature Edition')
                ->exists())->toBeTrue();
    }

    expect(InvitationTemplate::query()->count())->toBeGreaterThanOrEqual(620)
        ->and(InvitationTemplate::query()->where('category', 'christmas')->where('name', 'like', '% - Signature Edition')->exists())->toBeTrue()
        ->and(InvitationTemplate::query()->where('name', 'like', '% - Signature Edition')->count())->toBe(53)
        ->and(InvitationTemplate::query()->where('name', 'like', '% - Signature Edition')->distinct()->count('background_path'))->toBe(53)
        ->and(InvitationTemplate::query()->where('category', 'wedding')->pluck('name')->sort()->values()->all())
        ->toBe([
            'Wedding - Acacia',
            'Wedding - Autumn Border',
            'Wedding - Baobab',
            'Wedding - Blue Toile',
            'Wedding - Blush Roses',
            'Wedding - Bougainvillea',
            'Wedding - Burgundy Frame',
            'Wedding - Clay Arch',
            'Wedding - Couple Arch',
            'Wedding - Flower Column',
            'Wedding - Frangipani',
            'Wedding - Kanga Border',
            'Wedding - Lace Oval',
            'Wedding - Manor Line',
            'Wedding - Mosaic Gold',
            'Wedding - Open Frame',
            'Wedding - Orange Wreath',
            'Wedding - Palace Court',
            'Wedding - Palm Court',
            'Wedding - Papyrus',
            'Wedding - Signature Edition',
            'Wedding - Torn Paper',
            'Wedding - Violet Wreath',
            'Wedding - Wildflower Foot',
        ]);

    $silk = InvitationTemplate::query()->where('name', 'Birthday - Silk Night')->first();
    $medallion = InvitationTemplate::query()->where('name', 'Birthday - Gold Medallion')->first();

    expect($silk)->not->toBeNull()
        ->and($silk->host_only)->toBeFalse()
        ->and($silk->design()['scene'])->toBe('silk')
        ->and($silk->galleryFace()['title'])->toBe('Neema')
        ->and($medallion->design()['scene'])->toBe('medallion')
        ->and($medallion->portraitSlot()['shape'])->toBe('circle');
});

test('reseeding keeps a customised starter template', function () {
    $this->seed(InvitationTemplateSeeder::class);

    $template = InvitationTemplate::query()->where('name', 'Wedding - Acacia')->firstOrFail();
    $template->forceFill([
        'price' => 15000,
        'host_only' => true,
        'default_headline' => 'Please join our wedding',
    ])->save();

    $this->seed(InvitationTemplateSeeder::class);

    $template->refresh();

    expect($template->price)->toBe(15000)
        ->and($template->host_only)->toBeTrue()
        ->and($template->default_headline)->toBe('Please join our wedding')
        ->and($template->background_path)->toBe('card-templates/acacia.jpg')
        ->and(InvitationTemplate::imageUrl('events/custom-card.jpg'))->toBe('/storage/events/custom-card.jpg');
});

test('photo cards lead the host template library', function () {
    $this->seed(InvitationTemplateSeeder::class);
    $host = User::factory()->create();

    $this->actingAs($host)
        ->get(route('template-library.index', ['category' => 'wedding']))
        ->assertOk()
        ->assertSeeInOrder(['Amina & Juma', 'Wedding - Couple Arch', 'Wedding - Acacia'], false);
});

test('the admin can view all starter templates within a category', function () {
    $this->seed(InvitationTemplateSeeder::class);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.templates.index', ['category' => 'wedding']))
        ->assertOk()
        ->assertSee('Wedding - Bougainvillea')
        ->assertSee('Wedding - Acacia')
        ->assertSee('Wedding - Kanga Border')
        ->assertSee('card-templates/bougainvillea.jpg', false);
});
