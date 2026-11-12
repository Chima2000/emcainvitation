<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TopUpRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
        $hasTerm = mb_strlen($term) >= 2;

        return view('admin.search', [
            'term' => $term,
            'hosts' => $hasTerm
                ? User::query()
                    ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like))
                    ->withCount('events')
                    ->limit(10)
                    ->get()
                : collect(),
            'events' => $hasTerm
                ? Event::query()
                    ->with('user:id,name')
                    ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('venue', 'like', $like)->orWhere('host_name', 'like', $like))
                    ->latest('event_date')
                    ->limit(10)
                    ->get()
                : collect(),
            'topUps' => $hasTerm
                ? TopUpRequest::query()
                    ->with('user:id,name')
                    ->where(fn ($query) => $query->where('reference', 'like', $like)->orWhere('payer_phone', 'like', $like))
                    ->latest()
                    ->limit(10)
                    ->get()
                : collect(),
        ]);
    }
}
