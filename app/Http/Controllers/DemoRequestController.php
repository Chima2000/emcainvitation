<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDemoRequest;
use App\Models\DemoRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DemoRequestController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin() ?? false, 404);

        return view('demos.index', [
            'demos' => DemoRequest::query()->latest()->paginate(20),
        ]);
    }

    public function store(StoreDemoRequest $request): RedirectResponse
    {
        DemoRequest::query()->create($request->validated());

        return redirect()
            ->route('home')
            ->withFragment('demo')
            ->with('status', __('Demo request received. We will contact you on the phone number you gave us.'));
    }
}
