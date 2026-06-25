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
        ]);

        if ($request->filled('operational_ticket_id')) {
            // Ensure user owns this ticket
            $ticket = OperationalTicket::where('id', $request->operational_ticket_id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();
        }

        $file = $request->file('file');
        
        // Store files in a private S3 directory (or fall back to local disk in dev)
        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';
        $path = $file->store('documents/' . $request->user()->id, $disk);

        $document = Document::create([
            'user_id' => $request->user()->id,
            'operational_ticket_id' => $request->operational_ticket_id,
            'name' => $request->name ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
        ]);

        return response()->json($document, 201);
    }

    public function index(Request $request)
    {
        $documents = $request->user()->documents()->latest()->get();
        return response()->json($documents);
    }

    public function download(Request $request, $id)
    {
        $document = $request->user()->documents()->findOrFail($id);
        
        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';

        if (!Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        return Storage::disk($disk)->download($document->file_path, $document->name);
    }
}
