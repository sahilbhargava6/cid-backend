<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\OperationalTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB Limit
            'operational_ticket_id' => 'nullable|exists:operational_tickets,id',
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:tax_filing,receipt,procurement_manifest,other',
        ]);

        if ($request->filled('operational_ticket_id')) {
            $isAdmin = $request->user()->role === 'admin' || $request->user()->role === 'owner';
            if ($isAdmin) {
                $ticket = OperationalTicket::findOrFail($request->operational_ticket_id);
            } else {
                // Ensure user owns this ticket
                $ticket = OperationalTicket::where('id', $request->operational_ticket_id)
                    ->where('user_id', $request->user()->id)
                    ->firstOrFail();
            }
        }

        $file = $request->file('file');
        
        // Store files in a private directory (or fall back to local disk in dev)
        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';
        
        $filename = uniqid() . '_' . $file->getClientOriginalName() . '.enc';
        $path = 'documents/' . ($request->filled('operational_ticket_id') ? $ticket->user_id : $request->user()->id) . '/' . $filename;

        // Encrypt content at rest
        $encryptedContents = \Illuminate\Support\Facades\Crypt::encrypt(file_get_contents($file->getRealPath()));
        Storage::disk($disk)->put($path, $encryptedContents);

        $document = Document::create([
            'user_id' => $request->filled('operational_ticket_id') ? $ticket->user_id : $request->user()->id,
            'operational_ticket_id' => $request->operational_ticket_id,
            'name' => $request->name ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'category' => $request->category ?? 'other',
        ]);

        return response()->json($document, 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';

        if ($isAdmin) {
            $documents = Document::latest()->get();
        } else {
            $documents = $user->documents()->latest()->get();
        }
        return response()->json($documents);
    }

    public function download(Request $request, $id)
    {
        $user = $request->user();
        if (!$user && $request->has('token')) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($tokenModel) {
                $user = $tokenModel->tokenable;
            }
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $isAdmin = $user->role === 'admin' || $user->role === 'owner';
        if ($isAdmin) {
            $document = Document::findOrFail($id);
        } else {
            $document = $user->documents()->findOrFail($id);
        }
        
        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';

        if (!Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        // Decrypt the file
        $encryptedContents = Storage::disk($disk)->get($document->file_path);
        try {
            $decryptedContents = \Illuminate\Support\Facades\Crypt::decrypt($encryptedContents);
        } catch (\Exception $e) {
            // Fallback for unencrypted files uploaded before migration
            $decryptedContents = $encryptedContents;
        }

        // Return preview or file download
        if ($request->query('preview') === 'true') {
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'txt' => 'text/plain',
            ];
            $contentType = $mimeTypes[strtolower($document->file_type)] ?? 'application/octet-stream';
            return response($decryptedContents, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="' . $document->name . '"',
            ]);
        }

        return response()->streamDownload(function () use ($decryptedContents) {
            echo $decryptedContents;
        }, $document->name);
    }
}
