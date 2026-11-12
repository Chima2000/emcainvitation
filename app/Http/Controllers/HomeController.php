<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\InvitationTemplate;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('pages.home', [
            'templates' => InvitationTemplate::query()->orderBy('name')->take(6)->get(),
            'upcomingEvents' => Event::query()->upcoming()->with('template')->take(4)->get(),
        ]);
    }
}
