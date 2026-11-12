<?php

use App\Models\EventCategory;
use App\Models\InvitationTemplate;
use App\Models\User;
use Database\Seeders\InvitationTemplateSeeder;

test('every event category has three reusable starter templates', function () {
    $this->seed(InvitationTemplateSeeder::class);
    $this->seed(InvitationTemplateSeeder::class);

    $categories = EventCategory::query()->withCount('templates')->get();

    expect($categories)->not->toBeEmpty();

    foreach ($categories as $category) {
        expect($category->templates_count)->toBe(3);
    }

    expect(InvitationTemplate::query()->count())->toBe(33);
});

test('the admin can view all starter templates within a category', function () {
    $this->seed(InvitationTemplateSeeder::class);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.templates.index', ['category' => 'wedding']))
        ->assertOk()
        ->assertSee('Wedding - Golden Classic')
        ->assertSee('Wedding - Botanical')
        ->assertSee('Wedding - Modern Rose');
});
