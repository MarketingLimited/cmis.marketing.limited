<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Contact\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * ContactController
 *
 * Handles CRUD operations for contacts in the CRM system.
 * Includes contact deduplication and merge functionality.
 */
class ContactController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all contacts with filtering and pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    : \Illuminate\Http\JsonResponse {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->unauthorized('Organization context required');
        }

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$orgId]);

        $query = Contact::query();

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('company', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->has('source')) {
            $query->fromSource($request->input('source'));
        }

        if ($request->has('is_subscribed')) {
            if ($request->boolean('is_subscribed')) {
                $query->subscribed();
            } else {
                $query->where('is_subscribed', false);
            }
        }

        if ($request->has('segment')) {
            $query->inSegment($request->input('segment'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $contacts = $query->paginate($perPage);

        return $this->paginated($contacts, 'Contacts retrieved successfully');
    }

    /**
     * Create a new contact
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->unauthorized('Organization context required');
        }

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$orgId]);

        // Validate request
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'segments' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'social_profiles' => 'nullable|array',
            'is_subscribed' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        // Check for duplicates
        $existingContact = Contact::where('email', $request->email)
            ->where('org_id', $orgId)
            ->first();

        if ($existingContact) {
            return $this->error('Contact with this email already exists', 409);
        }

        // Create contact
        $contactData = $validator->validated();
        $contactData['org_id'] = $orgId;

        $contact = Contact::create($contactData);

        return $this->created($contact, 'Contact created successfully');
    }

    /**
     * Show a specific contact
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->unauthorized('Organization context required');
        }

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$orgId]);

        $contact = Contact::with(['leads'])->find($id);

        if (!$contact) {
            return $this->notFound('Contact not found');
        }

        return $this->success($contact, 'Contact retrieved successfully');
    }

    /**
     * Update a contact
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(string $id, Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->unauthorized('Organization context required');
        }

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$orgId]);

        $contact = Contact::find($id);

        if (!$contact) {
            return $this->notFound('Contact not found');
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'segments' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'social_profiles' => 'nullable|array',
            'is_subscribed' => 'nullable|boolean',
            'last_engaged_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        // Check for duplicate email if email is being changed
        if ($request->has('email') && $request->email !== $contact->email) {
            $existingContact = Contact::where('email', $request->email)
                ->where('org_id', $orgId)
                ->where('contact_id', '!=', $id)
                ->first();

            if ($existingContact) {
                return $this->error('Contact with this email already exists', 409);
            }
        }

        $contact->update($validator->validated());

        return $this->success($contact, 'Contact updated successfully');
    }

    /**
     * Delete a contact (soft delete)
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->unauthorized('Organization context required');
        }

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$orgId]);

        $contact = Contact::find($id);

        if (!$contact) {
            return $this->notFound('Contact not found');
        }

        $contact->delete();

        return $this->deleted('Contact deleted successfully');
    }

    /**
     * Find duplicate contacts for a given contact
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findDuplicates(string $id, Request $request)
    : \Illuminate\Http\JsonResponse {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->unauthorized('Organization context required');
        }

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$orgId]);

        $contact = Contact::find($id);

        if (!$contact) {
            return $this->notFound('Contact not found');
        }

        // Find potential duplicates based on email, phone, or name
        $duplicates = Contact::where('contact_id', '!=', $id)
            ->where(function ($query) use ($contact) {
                if ($contact->email) {
                    $query->orWhere('email', $contact->email);
                }
                if ($contact->phone) {
                    $query->orWhere('phone', $contact->phone);
                }
                if ($contact->first_name && $contact->last_name) {
                    $query->orWhere(function ($q) use ($contact) {
                        $q->where('first_name', $contact->first_name)
                          ->where('last_name', $contact->last_name);
                    });
                }
            })
            ->get();

        return $this->success([
            'contact' => $contact,
            'duplicates' => $duplicates,
            'count' => $duplicates->count(),
        ], 'Duplicates found');
    }

    /**
     * Merge two contacts
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function merge(string $id, Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->unauthorized('Organization context required');
        }

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$orgId]);

        // Validate request
        $validator = Validator::make($request->all(), [
            'merge_with_id' => 'required|string|exists:cmis.contacts,contact_id',
            'keep_data_from' => 'nullable|in:primary,secondary',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $primaryContact = Contact::find($id);
        $secondaryContact = Contact::find($request->merge_with_id);

        if (!$primaryContact || !$secondaryContact) {
            return $this->notFound('One or both contacts not found');
        }

        DB::beginTransaction();
        try {
            // Determine which contact's data to keep
            $keepDataFrom = $request->input('keep_data_from', 'primary');

            if ($keepDataFrom === 'secondary') {
                // Swap primary and secondary
                [$primaryContact, $secondaryContact] = [$secondaryContact, $primaryContact];
            }

            // Merge segments and custom fields
            $mergedSegments = array_unique(array_merge(
                $primaryContact->segments ?? [],
                $secondaryContact->segments ?? []
            ));

            $mergedCustomFields = array_merge(
                $secondaryContact->custom_fields ?? [],
                $primaryContact->custom_fields ?? []
            );

            $mergedSocialProfiles = array_merge(
                $secondaryContact->social_profiles ?? [],
                $primaryContact->social_profiles ?? []
            );

            // Update primary contact with merged data
            $primaryContact->update([
                'segments' => $mergedSegments,
                'custom_fields' => $mergedCustomFields,
                'social_profiles' => $mergedSocialProfiles,
                'last_engaged_at' => max(
                    $primaryContact->last_engaged_at,
                    $secondaryContact->last_engaged_at
                ),
            ]);

            // Transfer all leads from secondary to primary
            DB::table('cmis.leads')
                ->where('contact_id', $secondaryContact->contact_id)
                ->update(['contact_id' => $primaryContact->contact_id]);

            // Soft delete the secondary contact
            $secondaryContact->delete();

            DB::commit();

            return $this->success([
                'merged_contact' => $primaryContact->fresh(['leads']),
                'deleted_contact_id' => $secondaryContact->contact_id,
            ], 'Contacts merged successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to merge contacts: ' . $e->getMessage());
        }
    }

    /**
     * Resolve organization ID from request
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveOrgId(Request $request): ?string
    {
        // Try request parameter first
        if ($request->has('org_id')) {
            return $request->input('org_id');
        }

        // Try authenticated user's organization
        $user = $request->user();
        if ($user && isset($user->org_id)) {
            return $user->org_id;
        }

        // Try header
        if ($request->hasHeader('X-Org-Id')) {
            return $request->header('X-Org-Id');
        }

        return null;
    }
}
