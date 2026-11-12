<?php

namespace App\Http\Controllers;

use App\CardFont;
use App\CardLayout;
use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\UpdateEventDesignRequest;
use App\Models\Event;
use App\Models\InvitationTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventDesignController extends Controller
{
    use ManagesOwnedEvent;

    public function edit(Request $request, Event $event): View
    {
        $this->ownedEvent($request, $event);

        $event->load('template');

        return view('events.design', [
            'event' => $event,
            'design' => $event->cardDesign(),
            'custom' => $event->card_design ?? [],
            'templates' => InvitationTemplate::query()->orderBy('name')->get(),
            'fonts' => CardFont::cases(),
            'layouts' => CardLayout::cases(),
            'defaultDesign' => InvitationTemplate::defaultDesign(),
        ]);
    }

    public function update(UpdateEventDesignRequest $request, Event $event): RedirectResponse
    {
        $template = InvitationTemplate::query()->find($request->validated('invitation_template_id'));
        $base = $template?->design() ?? InvitationTemplate::defaultDesign();
        $previous = $event->card_design ?? [];

        $custom = [];

        foreach (['paper', 'ink', 'accent'] as $color) {
            if (strcasecmp($request->validated($color), $base[$color]) !== 0) {
                $custom[$color] = strtolower($request->validated($color));
            }
        }

        if ($request->validated('font') !== $base['font']->value) {
            $custom['font'] = $request->validated('font');
        }

        if ($request->validated('layout') !== $base['layout']->value) {
            $custom['layout'] = $request->validated('layout');
        }

        $backgroundPath = $previous['background_path'] ?? null;

        if ($request->hasFile('background') || $request->boolean('remove_background')) {
            if ($backgroundPath) {
                Storage::disk('public')->delete($backgroundPath);
            }

            $backgroundPath = $request->hasFile('background')
                ? $request->file('background')->store('card-backgrounds/events', 'public')
                : null;
        }

        if ($backgroundPath) {
            $custom['background_path'] = $backgroundPath;
        }

        if ($request->boolean('hide_template_background')) {
            $custom['hide_background'] = true;
        }

        $event->update([
            'invitation_template_id' => $template?->id,
            'headline' => $request->validated('headline'),
            'message' => $request->validated('message'),
            'dress_code' => $request->validated('dress_code'),
            'card_design' => $custom === [] ? null : $custom,
        ]);

        return redirect()
            ->route('events.design.edit', $event)
            ->with('status', __('Card design saved. Guests see the new look straight away.'));
    }
}
