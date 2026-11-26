<?php

use Illuminate\Support\Facades\File;

test('the database backup command writes a sql file and removes it after two weeks', function () {
    $directory = storage_path('app/backups');
    File::ensureDirectoryExists($directory);
    $stale = $directory.'/database-20000101-000000.sql';
    File::put($stale, '-- stale');
    touch($stale, now()->subDays(15)->getTimestamp());

    $this->artisan('backup:database')->assertSuccessful();

    expect(File::exists($stale))->toBeFalse();

    $fresh = collect(File::files($directory))
        ->filter(fn (SplFileInfo $file): bool => str_starts_with($file->getFilename(), 'database-') && $file->getFilename() !== 'database-20000101-000000.sql');

    expect($fresh)->not->toBeEmpty()
        ->and(File::get($fresh->first()->getPathname()))->toContain('INSERT INTO');

    $fresh->each(fn (SplFileInfo $file) => File::delete($file->getPathname()));
});

test('the public demo form stops accepting requests after six in a minute', function () {
    $payload = [
        'name' => 'Neema Ally',
        'email' => 'neema@example.com',
        'phone' => '0712345678',
    ];

    foreach (range(1, 6) as $attempt) {
        $this->post(route('demos.store'), $payload)->assertRedirect();
    }

    $this->post(route('demos.store'), $payload)->assertStatus(429);
});
