<?php

namespace App\Support;

use App\Models\EventCategory;
use Illuminate\Support\Collection;

/**
 * Loads event categories once per request so casts and pickers do not query repeatedly.
 */
class EventCategoryRegistry
{
    /**
     * @var Collection<string, EventCategory>|null
     */
    private ?Collection $categories = null;

    /**
     * @return Collection<string, EventCategory>
     */
    public function all(): Collection
    {
        return $this->categories ??= EventCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->keyBy('slug');
    }

    /**
     * @return Collection<int, EventCategory>
     */
    public function active(): Collection
    {
        return $this->all()->where('is_active', true)->values();
    }

    public function find(?string $slug): ?EventCategory
    {
        if (blank($slug)) {
            return null;
        }

        return $this->all()->get($slug);
    }

    public function flush(): void
    {
        $this->categories = null;
    }
}
