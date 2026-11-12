<?php

namespace App\Casts;

use App\Models\EventCategory;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores an event category by slug and reads it back as the admin-managed category.
 *
 * @implements CastsAttributes<EventCategory|null, EventCategory|string|null>
 */
class EventCategorySlug implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EventCategory
    {
        if (blank($value)) {
            return null;
        }

        return EventCategory::findBySlug((string) $value) ?? EventCategory::placeholder((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value instanceof EventCategory) {
            return $value->slug;
        }

        return blank($value) ? null : (string) $value;
    }
}
