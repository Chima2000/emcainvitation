<?php

namespace App\Models;

use App\ServiceItem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable(['item', 'amount'])]
class ServiceRate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item' => ServiceItem::class,
            'amount' => 'integer',
        ];
    }

    /**
     * Current price of every billable item, falling back to the launch price when a row is missing.
     *
     * @return Collection<string, int>
     */
    public static function table(): Collection
    {
        $saved = static::query()->pluck('amount', 'item');

        return collect(ServiceItem::cases())->mapWithKeys(fn (ServiceItem $item): array => [
            $item->value => (int) ($saved[$item->value] ?? $item->defaultAmount()),
        ]);
    }

    public static function amountFor(ServiceItem $item): int
    {
        return (int) (static::query()->where('item', $item)->value('amount') ?? $item->defaultAmount());
    }
}
