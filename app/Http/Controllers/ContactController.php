<?php

namespace App\Http\Controllers;

use App\GuestGender;
use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        return view('contacts.index', [
            'contacts' => $request->user()->contacts()->simplePaginate(6),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::enum(GuestGender::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Contact::remember(
            $request->user(),
            $validated['name'],
            $validated['phone'] ?? null,
            $validated['email'] ?? null,
            $validated['gender'] ?? null,
        );

        if (filled($validated['notes'] ?? null)) {
            $request->user()->contacts()
                ->where('name', $validated['name'])
                ->when(filled($validated['phone'] ?? null), fn ($query) => $query->where('phone', $validated['phone']))
                ->first()
                ?->update(['notes' => $validated['notes']]);
        }

        return redirect()
            ->route('contacts.index')
            ->with('status', __('Contact saved.'));
    }

    public function destroy(Request $request, Contact $contact): RedirectResponse
    {
        abort_unless($contact->user_id === $request->user()->id, 404);

        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('status', __('Contact removed.'));
    }
}
