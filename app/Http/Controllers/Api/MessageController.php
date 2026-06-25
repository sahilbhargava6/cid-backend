<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\OperationalTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index(Request $request, $ticketId)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';

        if ($isAdmin) {
            $ticket = OperationalTicket::findOrFail($ticketId);
        } else {
            $ticket = $user->tickets()->findOrFail($ticketId);
        }

        $messages = Message::where('operational_ticket_id', $ticket->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function store(Request $request, $ticketId)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';

        if ($isAdmin) {
            $ticket = OperationalTicket::findOrFail($ticketId);
        } else {
            $ticket = $user->tickets()->findOrFail($ticketId);
        }

        $request->validate([
            'message_text' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $attachmentName = $file->getClientOriginalName();
            // Store file using Laravel default disk (S3 in production, public locally)
            $attachmentPath = $file->store('chat-attachments');
        }

        $message = Message::create([
            'operational_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message_text' => $request->message_text,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        // Eager load sender user details
        $message->load('user');

        return response()->json($message, 201);
    }
}
