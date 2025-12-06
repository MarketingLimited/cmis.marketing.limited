<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Subscription\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Demo Controller
 *
 * Handles demo request form submissions.
 * Saves to cmis.leads table with type='demo'.
 */
class DemoController extends Controller
{
    use ApiResponse;

    /**
     * Display the demo request page.
     */
    public function show()
    {
        // Get available plans for the form
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['plan_id', 'name', 'description']);

        return view('marketing.demo', compact('plans'));
    }

    /**
     * Handle demo request form submission.
     *
     * Saves to cmis.leads table with type='demo'.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'required|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'company_size' => 'nullable|string|in:1-10,11-50,51-200,201-500,501-1000,1000+',
            'industry' => 'nullable|string|max:255',
            'interested_plan' => 'nullable|uuid|exists:cmis.plans,plan_id',
            'use_case' => 'nullable|string|max:2000',
            'preferred_date' => 'nullable|date|after:today',
            'preferred_time' => 'nullable|string|in:morning,afternoon,evening',
            'timezone' => 'nullable|string|max:100',
            'consent' => 'required|accepted',
        ]);

        try {
            $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

            // Insert into cmis.leads table
            $leadId = Str::uuid();
            DB::table('cmis.leads')->insert([
                'lead_id' => $leadId,
                'type' => 'demo',
                'name' => $fullName,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'company_name' => $validated['company'],
                'job_title' => $validated['job_title'] ?? null,
                'source' => 'website_demo_form',
                'status' => 'new',
                'metadata' => json_encode([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'company_size' => $validated['company_size'] ?? null,
                    'industry' => $validated['industry'] ?? null,
                    'interested_plan' => $validated['interested_plan'] ?? null,
                    'use_case' => $validated['use_case'] ?? null,
                    'preferred_date' => $validated['preferred_date'] ?? null,
                    'preferred_time' => $validated['preferred_time'] ?? null,
                    'timezone' => $validated['timezone'] ?? null,
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'locale' => app()->getLocale(),
                    'referrer' => $request->header('referer'),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Optional: Send notification email
            // Mail::to(config('app.sales_email'))->send(new DemoRequested($validated));

            Log::info('Demo request submitted', [
                'lead_id' => $leadId,
                'email' => $validated['email'],
                'company' => $validated['company'],
            ]);

            if ($request->expectsJson()) {
                return $this->success([
                    'message' => __('marketing.demo.success_message'),
                ]);
            }

            return redirect()
                ->route('marketing.demo')
                ->with('success', __('marketing.demo.success_message'));

        } catch (\Exception $e) {
            Log::error('Failed to submit demo request', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
            ]);

            if ($request->expectsJson()) {
                return $this->serverError(__('marketing.demo.error_message'));
            }

            return back()
                ->withInput()
                ->with('error', __('marketing.demo.error_message'));
        }
    }
}
