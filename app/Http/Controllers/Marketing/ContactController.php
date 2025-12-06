<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Contact Controller
 *
 * Handles contact form submissions.
 * Saves to cmis.leads table.
 */
class ContactController extends Controller
{
    use ApiResponse;

    /**
     * Display the contact page.
     */
    public function show()
    {
        // Get contact information from website settings
        $contactInfo = [
            'email' => config('app.contact_email', 'contact@example.com'),
            'phone' => config('app.contact_phone', '+1-555-123-4567'),
            'address' => config('app.contact_address', '123 Business Street, City, Country'),
        ];

        return view('marketing.contact', compact('contactInfo'));
    }

    /**
     * Handle contact form submission.
     *
     * Saves to cmis.leads table with type='contact'.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'consent' => 'required|accepted',
        ]);

        try {
            // Insert into cmis.leads table
            $leadId = Str::uuid();
            DB::table('cmis.leads')->insert([
                'lead_id' => $leadId,
                'type' => 'contact',
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'company_name' => $validated['company'] ?? null,
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'source' => 'website_contact_form',
                'status' => 'new',
                'metadata' => json_encode([
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'locale' => app()->getLocale(),
                    'referrer' => $request->header('referer'),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Optional: Send notification email to admin
            // Mail::to(config('app.admin_email'))->send(new ContactFormSubmitted($validated));

            Log::info('Contact form submitted', [
                'lead_id' => $leadId,
                'email' => $validated['email'],
            ]);

            if ($request->expectsJson()) {
                return $this->success([
                    'message' => __('marketing.contact.success_message'),
                ]);
            }

            return redirect()
                ->route('marketing.contact')
                ->with('success', __('marketing.contact.success_message'));

        } catch (\Exception $e) {
            Log::error('Failed to submit contact form', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
            ]);

            if ($request->expectsJson()) {
                return $this->serverError(__('marketing.contact.error_message'));
            }

            return back()
                ->withInput()
                ->with('error', __('marketing.contact.error_message'));
        }
    }
}
