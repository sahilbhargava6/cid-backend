<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperationalTicket;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = str_contains($user->email, 'admin') || str_contains($user->email, 'owner');

        if ($isAdmin) {
            $tickets = OperationalTicket::with(['organization', 'assignee', 'user'])
                ->latest()
                ->get();
        } else {
            $tickets = $user->tickets()
                ->with(['organization', 'assignee'])
                ->latest()
                ->get();
        }

        return response()->json($tickets);
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_type' => 'required|string|in:tax_prep,virtual_bookkeeping,solar,accounts_and_logistics,procurement,tax_preparation',
            'scheduled_at' => 'nullable|date',
            'organization_id' => 'nullable|exists:organizations,id',
            'input_parameters' => 'required|array',
        ]);

        $ticket = OperationalTicket::create([
            'user_id' => $request->user()->id,
            'organization_id' => $request->organization_id,
            'service_type' => $request->service_type,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'input_parameters' => $request->input_parameters,
        ]);

        return response()->json($ticket, 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $isAdmin = str_contains($user->email, 'admin') || str_contains($user->email, 'owner');

        if ($isAdmin) {
            $ticket = OperationalTicket::with(['organization', 'assignee', 'documents', 'user'])
                ->findOrFail($id);
        } else {
            $ticket = $user->tickets()
                ->with(['organization', 'assignee', 'documents'])
                ->findOrFail($id);
        }

        return response()->json($ticket);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $isAdmin = str_contains($user->email, 'admin') || str_contains($user->email, 'owner');

        if ($isAdmin) {
            $ticket = OperationalTicket::findOrFail($id);
        } else {
            $ticket = $user->tickets()->findOrFail($id);
        }

        $request->validate([
            'input_parameters' => 'nullable|array',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'payment_status' => 'nullable|string|in:unpaid,partial,paid',
            'price' => 'nullable|numeric',
        ]);

        if ($request->has('input_parameters')) {
            $ticket->input_parameters = array_merge($ticket->input_parameters ?? [], $request->input_parameters);
        }
        if ($request->has('status')) {
            $ticket->status = $request->status;
        }
        if ($request->has('payment_status')) {
            $ticket->payment_status = $request->payment_status;
        }
        if ($request->has('price')) {
            $ticket->price = $request->price;
        }
        $ticket->save();

        return response()->json($ticket);
    }
}
