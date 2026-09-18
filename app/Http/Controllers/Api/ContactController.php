<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Submit contact form, store inquiry in DB, and notify admin via email
     */
    public function submit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:5000',
        ]);

        // 1. Store inquiry in database
        try {
            $inquiry = ContactInquiry::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'message' => $request->message,
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save contact inquiry to database: ' . $e->getMessage());
        }

        // 2. Dispatch notification email to admin
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

            $fromAddress = config('mail.from.address', 'service@consider-itdone.com');
            $fromName = config('mail.from.name', 'consider-itdone Contact Form');

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
                function ($message) use ($request, $recipients, $fromAddress, $fromName) {
                    $message->from($fromAddress, $fromName)
                            ->to($recipients)
                            ->replyTo($request->email)
                            ->subject("New Contact Form Inquiry from {$request->name}");
                }
            );

            Log::info("Contact inquiry email dispatched successfully for: {$request->email}");
        } catch (\Exception $e) {
            Log::error('Failed to send contact form email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Your inquiry has been successfully received. We will contact you shortly.'
        ]);
    }
}
