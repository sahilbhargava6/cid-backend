<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperationalTicket;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';

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

        // Send Email Notification to Admin
        try {
            $admins = \App\Models\User::whereIn('role', ['admin', 'owner'])->pluck('email')->toArray();
            $adminEmail = !empty($admins) ? $admins : 'admin@consideritdone.com';
            
            $user = $request->user();
            $serviceName = ucwords(str_replace('_', ' ', $ticket->service_type));
            
            \Illuminate\Support\Facades\Mail::raw(
                "Hello Admin,\n\n" .
                "A new booking has been placed on consider-itdone.\n\n" .
                "Booking Details:\n" .
                "- Booking ID: #{$ticket->id}\n" .
                "- Service: {$serviceName}\n" .
                "- Scheduled At: " . ($ticket->scheduled_at ?? 'N/A') . "\n" .
                "- Client Name: {$user->name}\n" .
                "- Client Email: {$user->email}\n\n" .
                "Please log in to the admin panel to review and manage this booking.\n\n" .
                "Best regards,\nconsider-itdone Notification System",
                function ($message) use ($adminEmail) {
                    $message->to($adminEmail)
                            ->subject('New Booking Received - consider-itdone');
                }
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send booking notification email: ' . $e->getMessage());
        }

        return response()->json($ticket, 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';

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
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';

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

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin' || $user->role === 'owner';

        if (!$isAdmin) {
            return response()->json(['message' => 'Unauthorized. Only admins can delete bookings.'], 403);
        }

        $ticket = OperationalTicket::findOrFail($id);
        $ticket->delete();

        return response()->json(['message' => 'Booking deleted successfully.']);
    }

    public function checkout(Request $request, $id)
    {
        $user = $request->user();
        
        // Find ticket ensuring it belongs to user
        $ticket = $user->tickets()->findOrFail($id);

        if ($ticket->payment_status === 'paid') {
            return response()->json(['error' => 'This booking is already paid.'], 400);
        }

        if (!$ticket->price || $ticket->price <= 0) {
            return response()->json(['error' => 'Price has not been set for this booking yet. Please wait for the admin to review.'], 400);
        }

        $stripeSecret = config('services.stripe.secret');
        if (!$stripeSecret) {
            return response()->json(['error' => 'Stripe is not configured on the server.'], 500);
        }

        Stripe::setApiKey($stripeSecret);

        $serviceName = ucwords(str_replace('_', ' ', $ticket->service_type));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Consider it Done - ' . $serviceName,
                        'description' => 'Booking #' . $ticket->id,
                    ],
                    'unit_amount' => (int) ($ticket->price * 100), // Convert dollars to cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('app.frontend_url', 'http://localhost:3000') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.frontend_url', 'http://localhost:3000') . '/payment/cancel',
            'client_reference_id' => $user->id,
            'metadata' => [
                'operational_ticket_id' => $ticket->id,
            ],
            'customer_email' => $user->email,
        ]);

        return response()->json(['url' => $session->url]);
    }
}
