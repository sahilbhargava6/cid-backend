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
            
            $filename = uniqid() . '_' . $file->getClientOriginalName() . '.enc';
            $attachmentPath = 'chat-attachments/' . $filename;
            
            // Encrypt and store the file
            $encryptedContents = \Illuminate\Support\Facades\Crypt::encrypt(file_get_contents($file->getRealPath()));
            Storage::disk('public')->put($attachmentPath, $encryptedContents);
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

    public function downloadAttachment(Request $request, $id)
    {
        $user = $request->user();
        if (!$user && $request->has('token')) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($tokenModel) {
                $user = $tokenModel->tokenable;
            }
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $message = Message::findOrFail($id);
        
        // Ensure user belongs to the ticket
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';
        if (!$isAdmin && $message->ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$message->attachment_path || !Storage::disk('public')->exists($message->attachment_path)) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        $encryptedContents = Storage::disk('public')->get($message->attachment_path);
        try {
            $decryptedContents = \Illuminate\Support\Facades\Crypt::decrypt($encryptedContents);
        } catch (\Exception $e) {
            $decryptedContents = $encryptedContents;
        }

        return response()->streamDownload(function () use ($decryptedContents) {
            echo $decryptedContents;
        }, $message->attachment_name);
    }
}
