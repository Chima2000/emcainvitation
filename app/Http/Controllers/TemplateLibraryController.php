<?php

namespace App\Http\Controllers;

use App\Models\EventCategory;
use App\Models\InvitationTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TemplateLibraryController extends Controller
{
    public function index(Request $request): View
    {
        $category = EventCategory::findBySlug((string) $request->query('category', ''));

        $templates = InvitationTemplate::query()
            ->when($category, fn ($query) => $query->where('category', $category->slug))
            ->orderBy('name')
            ->get();

        return view('templates.library', [
            'templates' => $templates,
            'categories' => EventCategory::options(),
            'activeCategory' => $category,
        ]);
    }
}
