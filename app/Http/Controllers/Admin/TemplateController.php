<?php

namespace App\Http\Controllers\Admin;

use App\CardFont;
use App\CardLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TemplateRequest;
use App\Models\EventCategory;
use App\Models\InvitationTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    public function index(Request $request): View
    {
        $category = EventCategory::findBySlug((string) $request->query('category', ''));

        $templateCounts = InvitationTemplate::query()
            ->selectRaw('category, count(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        return view('admin.templates.index', [
            'templates' => InvitationTemplate::query()
                ->withCount('events')
                ->when($category, fn ($query) => $query->where('category', $category->slug))
                ->orderBy('name')
                ->paginate(24)
                ->withQueryString(),
            'filterCategories' => EventCategory::catalog()
                ->filter(fn (EventCategory $option) => $option->is_active || $templateCounts->has($option->slug))
                ->values(),
            'categories' => EventCategory::catalog(),
            'defaultCategory' => EventCategory::options()->first()?->slug,
            'templateCounts' => $templateCounts,
            'totalTemplates' => $templateCounts->sum(),
            'category' => $category,
            'fonts' => CardFont::cases(),
            'layouts' => CardLayout::cases(),
        ]);
    }

    public function store(TemplateRequest $request): RedirectResponse
    {
        InvitationTemplate::query()->create([
            ...$request->templateAttributes(),
            'background_path' => $request->file('background')?->store('card-backgrounds/templates', 'public'),
        ]);

        return redirect()
            ->route('admin.templates.index')
            ->with('status', __('Card template saved.'));
    }

    public function update(TemplateRequest $request, InvitationTemplate $template): RedirectResponse
    {
        $attributes = $request->templateAttributes();

        if ($request->hasFile('background') || $request->boolean('remove_background')) {
            if ($template->background_path) {
                Storage::disk('public')->delete($template->background_path);
            }

            $attributes['background_path'] = $request->file('background')?->store('card-backgrounds/templates', 'public');
        }

        $template->update($attributes);

        return redirect()
            ->route('admin.templates.index')
            ->with('status', __('Card template updated.'));
    }

    public function destroy(InvitationTemplate $template): RedirectResponse
    {
        if ($template->background_path) {
            Storage::disk('public')->delete($template->background_path);
        }

        $template->delete();

        return redirect()
            ->route('admin.templates.index')
            ->with('status', __('Card template deleted. Events that used it keep their details with the default design.'));
    }
}
