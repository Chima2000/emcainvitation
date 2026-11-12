<?php

use App\QueueName;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:prune-failed --hours=336')->daily()->onOneServer();
Schedule::command('queue:prune-batches --hours=48 --unfinished=72')->daily()->onOneServer();

if (config('queue.work_from_scheduler')) {
    Schedule::command('queue:work --queue='.QueueName::workerList().' --max-time=55 --sleep=1 --tries=3')
        ->everyMinute()
        ->withoutOverlapping(5)
        ->runInBackground();
}
