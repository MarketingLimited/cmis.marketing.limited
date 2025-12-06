<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Newsletter Controller
 *
 * Handles newsletter subscription form.
 * Saves to cmis.contacts table.
 */
class NewsletterController extends Controller
{
    use ApiResponse;

    /**
     * Subscribe to the newsletter.
     *
     * Saves to cmis.contacts table.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            // Check if email already exists
            $exists = DB::table('cmis.contacts')
                ->where('email', $validated['email'])
                ->where('type', 'newsletter')
                ->exists();

            if ($exists) {
                if ($request->expectsJson()) {
                    return $this->success([
                        'message' => __('marketing.newsletter.already_subscribed'),
                    ]);
                }

                return back()->with('info', __('marketing.newsletter.already_subscribed'));
            }

            // Insert into cmis.contacts table
            $contactId = Str::uuid();
            DB::table('cmis.contacts')->insert([
                'contact_id' => $contactId,
                'type' => 'newsletter',
                'email' => $validated['email'],
                'name' => $validated['name'] ?? null,
                'status' => 'subscribed',
                'source' => 'website_newsletter_form',
                'subscribed_at' => now(),
                'metadata' => json_encode([
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'locale' => app()->getLocale(),
                    'referrer' => $request->header('referer'),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Newsletter subscription', [
                'contact_id' => $contactId,
                'email' => $validated['email'],
            ]);

            if ($request->expectsJson()) {
                return $this->success([
                    'message' => __('marketing.newsletter.success_message'),
                ]);
            }

            return back()->with('success', __('marketing.newsletter.success_message'));

        } catch (\Exception $e) {
            Log::error('Failed to subscribe to newsletter', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
            ]);

            if ($request->expectsJson()) {
                return $this->serverError(__('marketing.newsletter.error_message'));
            }

            return back()
                ->withInput()
                ->with('error', __('marketing.newsletter.error_message'));
        }
    }

    /**
     * Unsubscribe from the newsletter.
     *
     * This is typically accessed via a link in the email.
     */
    public function unsubscribe(Request $request, string $token)
    {
        try {
            $contact = DB::table('cmis.contacts')
                ->where('unsubscribe_token', $token)
                ->where('type', 'newsletter')
                ->first();

            if (!$contact) {
                return view('marketing.newsletter.unsubscribe', [
                    'success' => false,
                    'message' => __('marketing.newsletter.invalid_token'),
                ]);
            }

            DB::table('cmis.contacts')
                ->where('contact_id', $contact->contact_id)
                ->update([
                    'status' => 'unsubscribed',
                    'unsubscribed_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::info('Newsletter unsubscription', [
                'contact_id' => $contact->contact_id,
                'email' => $contact->email,
            ]);

            return view('marketing.newsletter.unsubscribe', [
                'success' => true,
                'message' => __('marketing.newsletter.unsubscribed_message'),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe from newsletter', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);

            return view('marketing.newsletter.unsubscribe', [
                'success' => false,
                'message' => __('marketing.newsletter.error_message'),
            ]);
        }
    }
}
