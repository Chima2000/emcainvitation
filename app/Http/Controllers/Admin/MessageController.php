<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\MessageChannel;
use App\MessagePurpose;
use App\Models\OutboundMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $purpose = MessagePurpose::tryFrom((string) $request->query('purpose', ''));
        $channel = MessageChannel::tryFrom((string) $request->query('channel', ''));

        $filtered = OutboundMessage::query()
            ->when($purpose, fn ($query) => $query->where('purpose', $purpose))
            ->when($channel, fn ($query) => $query->where('channel', $channel));

        return view('admin.messages.index', [
            'messages' => (clone $filtered)
                ->with(['guest:id,name,phone', 'event:id,title,user_id', 'event.user:id,name'])
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'totals' => [
                'messages' => (clone $filtered)->count(),
                'covered' => (clone $filtered)->where('covered_by_package', true)->count(),
                'charged' => (int) (clone $filtered)->sum('cost'),
            ],
            'purpose' => $purpose,
            'channel' => $channel,
        ]);
    }
}
