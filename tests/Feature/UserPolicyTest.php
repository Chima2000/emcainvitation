<?php

use App\Models\User;
use App\Policies\UserPolicy;

test('a super admin can manage host accounts', function () {
    $policy = new UserPolicy;
    $admin = User::factory()->superAdmin()->make();
    $admin->id = 1;
    $host = User::factory()->make();
    $host->id = 2;

    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->view($admin, $host))->toBeTrue()
        ->and($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $host))->toBeTrue()
        ->and($policy->delete($admin, $host))->toBeTrue();
});

test('a host cannot manage accounts', function () {
    $policy = new UserPolicy;
    $host = User::factory()->make();
    $host->id = 3;
    $other = User::factory()->make();
    $other->id = 4;

    expect($policy->viewAny($host))->toBeFalse()
        ->and($policy->view($host, $other))->toBeFalse()
        ->and($policy->create($host))->toBeFalse()
        ->and($policy->update($host, $other))->toBeFalse()
        ->and($policy->delete($host, $other))->toBeFalse();
});

test('a super admin cannot delete their own account or another super admin', function () {
    $policy = new UserPolicy;
    $admin = User::factory()->superAdmin()->make();
    $admin->id = 1;
    $otherAdmin = User::factory()->superAdmin()->make();
    $otherAdmin->id = 2;

    expect($policy->delete($admin, $admin))->toBeFalse()
        ->and($policy->delete($admin, $otherAdmin))->toBeFalse();
});
