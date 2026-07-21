<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Submit contact form and notify admin
     */
    public function submit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:5000',
        ]);

        try {
            $admins = \App\Models\User::whereIn('role', ['admin', 'owner'])->pluck('email')->toArray();
            $recipients = !empty($admins) ? $admins : [];

            $siteConfig = \App\Models\SiteSetting::where('key', 'site_config')->first();
            if ($siteConfig && !empty($siteConfig->value['contactEmail'])) {
                $recipients[] = $siteConfig->value['contactEmail'];
            }

            $recipients = array_values(array_unique(array_filter($recipients)));
            if (empty($recipients)) {
                $recipients = ['service@consider-itdone.com'];
            }

            Mail::raw(
                "Hello Admin,\n\n" .
                "You have received a new contact inquiry from the consider-itdone website.\n\n" .
                "Inquiry Details:\n" .
                "- Name: {$request->name}\n" .
                "- Email: {$request->email}\n" .
                "- Phone: " . ($request->phone ?? 'N/A') . "\n\n" .
                "Message:\n" .
                "\"{$request->message}\"\n\n" .
                "Best regards,\nconsider-itdone Contact Form",
                function ($message) use ($request, $recipients) {
                    $message->to($recipients)
                            ->replyTo($request->email)
                            ->subject("New Contact Form Inquiry from {$request->name}");
                }
            );
        } catch (\Exception $e) {
            Log::error('Failed to send contact form email: ' . $e->getMessage());
            // We can still return success because the backend received the inquiry
        }

        return response()->json([
            'success' => true,
            'message' => 'Your inquiry has been successfully received. We will contact you shortly.'
        ]);
    }
}
