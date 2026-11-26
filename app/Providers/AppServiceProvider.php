<?php

namespace App\Providers;

use App\EventStatus;
use App\Jobs\SendGuestSms;
use App\Models\DemoRequest;
use App\Models\EventCategory;
use App\Models\InvitationTemplate;
use App\Models\TopUpRequest;
use App\Rules\ContainsUppercaseLetter;
use App\Support\ChosenInvitationTemplate;
use App\Support\EventCategoryRegistry;
use App\TopUpStatus;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(EventCategoryRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local') && ! $this->app->runningInConsole()) {
            $root = request()->getSchemeAndHttpHost();

            if (str_starts_with((string) request()->header('x-forwarded-proto'), 'https')) {
                $root = preg_replace('#^http:#', 'https:', $root) ?? $root;
            }

            URL::forceRootUrl($root);
        }

        Password::defaults(fn (): Password => Password::min(9)
            ->numbers()
            ->symbols()
            ->rules([new ContainsUppercaseLetter]));

        Paginator::useTailwind();

        RateLimiter::for(SendGuestSms::RateLimiter, fn (): Limit => Limit::perMinute(max(1, (int) config('services.sms.per_minute'))));

        View::composer('events.partials.create-modal', function ($view): void {
            $view->with([
                'templates' => InvitationTemplate::query()->orderedForGallery()->get(),
                'types' => EventCategory::options(),
                'statuses' => EventStatus::cases(),
                'selectedTemplate' => app(ChosenInvitationTemplate::class)->id(request()),
            ]);
        });

        View::composer('layouts.admin', function ($view): void {
            $pending = TopUpRequest::query()->where('status', TopUpStatus::Pending);

            $view->with([
                'adminPendingTopUpCount' => (clone $pending)->count(),
                'adminPendingTopUps' => (clone $pending)->with('user:id,name')->latest()->limit(6)->get(),
                'adminNewDemos' => DemoRequest::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ]);
        });
    }
}
