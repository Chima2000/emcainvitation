<?php

namespace App\Models;

use App\SubscriptionPeriod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['period', 'price', 'is_active'])]
class SubscriptionPlan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => SubscriptionPeriod::class,
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<SubscriptionPlan>  $query
     * @return Builder<SubscriptionPlan>
     */
    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('price', '>', 0);
    }

    /**
     * Plans in period order, shortest first.
     *
     * @return Collection<int, SubscriptionPlan>
     */
    public static function ordered(bool $onlyAvailable = false): Collection
    {
        $order = array_flip(array_map(fn (SubscriptionPeriod $period): string => $period->value, SubscriptionPeriod::cases()));

        return static::query()
            ->when($onlyAvailable, fn (Builder $query) => $query->available())
            ->get()
            ->sortBy(fn (SubscriptionPlan $plan): int => $order[$plan->period->value] ?? PHP_INT_MAX)
            ->values();
    }

    public static function forPeriod(SubscriptionPeriod $period): ?self
    {
        return static::query()->available()->where('period', $period)->first();
    }
}
