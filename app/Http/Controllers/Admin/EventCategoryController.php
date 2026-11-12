<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventCategoryRequest;
use App\Models\EventCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.event-categories.index', [
            'categories' => EventCategory::query()
                ->withCount(['events', 'templates'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'icons' => EventCategory::iconChoices(),
            'photos' => EventCategory::stockPhotos(),
        ]);
    }

    public function store(EventCategoryRequest $request): RedirectResponse
    {
        $attributes = $request->categoryAttributes();

        EventCategory::query()->create([
            ...$attributes,
            'slug' => $this->uniqueSlug($attributes['name']),
            'photo_path' => $request->file('photo_upload')?->store('event-categories', 'public'),
        ]);

        return redirect()
            ->route('admin.event-categories.index')
            ->with('status', __('Event category saved.'));
    }

    public function update(EventCategoryRequest $request, EventCategory $eventCategory): RedirectResponse
    {
        $attributes = $request->categoryAttributes();

        if ($request->hasFile('photo_upload') || $request->boolean('remove_photo')) {
            if ($eventCategory->photo_path) {
                Storage::disk('public')->delete($eventCategory->photo_path);
            }

            $attributes['photo_path'] = $request->file('photo_upload')?->store('event-categories', 'public');
        }

        $eventCategory->update($attributes);

        return redirect()
            ->route('admin.event-categories.index')
            ->with('status', __('Event category updated.'));
    }

    public function destroy(EventCategory $eventCategory): RedirectResponse
    {
        if ($eventCategory->isInUse()) {
            $eventCategory->update(['is_active' => false]);

            return redirect()
                ->route('admin.event-categories.index')
                ->with('status', __('Events or templates already use this category, so it was switched off for hosts instead of deleted.'));
        }

        if ($eventCategory->photo_path) {
            Storage::disk('public')->delete($eventCategory->photo_path);
        }

        $eventCategory->delete();

        return redirect()
            ->route('admin.event-categories.index')
            ->with('status', __('Event category deleted.'));
    }

    /**
     * The slug is stored on events and templates, so it is fixed when the category is created.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 50, '') ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (EventCategory::query()->where('slug', $slug)->exists()) {
            $slug = $base.'_'.$suffix++;
        }

        return $slug;
    }
}
