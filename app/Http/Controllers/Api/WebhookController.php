<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperationalTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    public function setmore(Request $request)
    {
        // Shared-token validation for Setmore webhooks
        $token = $request->header('X-Setmore-Token');
        $expectedToken = config('services.setmore.webhook_token');

        if (!$expectedToken || $token !== $expectedToken) {
            Log::warning('Setmore Webhook Blocked: Unauthorized token attempt.');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        Log::info('Setmore webhook received & authenticated', $request->all());

        $email = $request->input('customer_email') ?? $request->input('email');
        if (!$email) {
            return response()->json(['error' => 'No email provided in webhook payload.'], 400);
        }

        // Find or create User
        $user = User::firstOrCreate([
            'email' => $email,
        ], [
            'name' => $request->input('customer_name', 'Setmore Guest'),
            'password' => bcrypt(str_random(16)), // Dummy password
        ]);

        // Map Setmore service key to our internal types
        $serviceName = strtolower($request->input('service_name', ''));
        $serviceType = 'small_business'; // Default fallback

        if (str_contains($serviceName, 'tax')) {
            $serviceType = 'tax_prep';
        } elseif (str_contains($serviceName, 'bookkeeping') || str_contains($serviceName, 'book')) {
            $serviceType = 'bookkeeping';
        } elseif (str_contains($serviceName, 'solar')) {
            $serviceType = 'solar';
        } elseif (str_contains($serviceName, 'procure')) {
            $serviceType = 'procurement';
        }

        // Create the operational ticket/booking
        $ticket = OperationalTicket::create([
            'user_id' => $user->id,
            'service_type' => $serviceType,
            'status' => 'pending',
            'scheduled_at' => $request->input('start_time'),
            'input_parameters' => [
                'webhook_source' => 'setmore',
                'setmore_appointment_id' => $request->input('appointment_id'),
                'raw_payload' => $request->all(),
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'ticket_id' => $ticket->id,
        ], 201);
    }

    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        if (!$endpointSecret) {
            Log::error('Stripe Webhook Blocked: Missing endpoint webhook secret in configuration.');
            return response()->json(['error' => 'Webhook secret configuration missing'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe Webhook Signature Verification Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook signature verified successfully: ' . $event->type);

        $dataObject = $event->data->object ?? [];
        $eventType = $event->type;

        if ($eventType === 'charge.succeeded' || $eventType === 'payment_intent.succeeded') {
            $ticketId = $dataObject['metadata']['operational_ticket_id'] ?? null;
            if ($ticketId) {
                $ticket = OperationalTicket::find($ticketId);
                if ($ticket) {
                    $ticket->update([
                        'payment_status' => 'paid',
                        'status' => 'in_progress', // update status upon receipt of payment
                    ]);
                }
            }
        }

        return response()->json(['status' => 'received']);
    }
}
