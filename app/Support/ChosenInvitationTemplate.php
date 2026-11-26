<?php

namespace App\Support;

use App\Models\InvitationTemplate;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Remembers the public card a visitor chose until their next new event opens.
 */
final class ChosenInvitationTemplate
{
    public const string SessionKey = 'chosen_invitation_template_id';

    public function rememberFromRequest(Request $request): void
    {
        if (! $request->exists('template')) {
            return;
        }

        $template = $this->find($request->integer('template'));

        if ($template instanceof InvitationTemplate) {
            $request->session()->put(self::SessionKey, $template->id);

            return;
        }

        $request->session()->forget(self::SessionKey);
    }

    public function id(Request $request): ?int
    {
        $sessionId = (int) $request->session()->get(self::SessionKey);
        $template = $this->find($request->integer('template')) ?? $this->find($sessionId);

        if (! $template instanceof InvitationTemplate) {
            if ($sessionId > 0) {
                $this->forget($request);
            }

            return null;
        }

        $user = $request->user();

        if ($user instanceof User && $user->isLead() && $template->host_only) {
            return null;
        }

        return $template->id;
    }

    public function eventUrl(Request $request): ?string
    {
        $templateId = $this->id($request);

        if ($templateId === null) {
            return null;
        }

        return route('events.index', ['new' => 1, 'template' => $templateId]);
    }

    public function forget(Request $request): void
    {
        $request->session()->forget(self::SessionKey);
    }

    private function find(int $templateId): ?InvitationTemplate
    {
        if ($templateId < 1) {
            return null;
        }

        $template = InvitationTemplate::query()->find($templateId);

        return $template instanceof InvitationTemplate ? $template : null;
    }
}
