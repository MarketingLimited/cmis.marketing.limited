<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Workflow\ApprovalWorkflow;
use App\Models\Social\ProfileGroup;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApprovalWorkflowSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of approval workflows.
     */
    public function index(Request $request, string $org)
    {
        $workflows = ApprovalWorkflow::where('org_id', $org)
            ->with(['profileGroup', 'creator'])
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return $this->success($workflows, 'Approval workflows retrieved successfully');
        }

        return view('settings.approval-workflows.index', [
            'workflows' => $workflows,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for creating a new approval workflow.
     */
    public function create(Request $request, string $org)
    {
        $profileGroups = ProfileGroup::where('org_id', $org)->get();
        $users = User::whereHas('orgMemberships', function ($query) use ($org) {
            $query->where('org_id', $org);
        })->get();

        return view('settings.approval-workflows.create', [
            'currentOrg' => $org,
            'profileGroups' => $profileGroups,
            'users' => $users,
            'triggerConditions' => $this->getTriggerConditions(),
        ]);
    }

    /**
     * Store a newly created approval workflow.
     */
    public function store(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'trigger_condition' => 'required|string|max:100',
            'approval_steps' => 'required|array|min:1',
            'approval_steps.*.approver_id' => 'required|uuid|exists:pgsql.cmis.users,user_id',
            'approval_steps.*.order' => 'required|integer|min:1',
            'approval_steps.*.required' => 'boolean',
            'auto_approve_after_hours' => 'nullable|integer|min:0',
            'notify_on_submit' => 'boolean',
            'notify_on_approve' => 'boolean',
            'notify_on_reject' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $workflow = ApprovalWorkflow::create([
                'org_id' => $org,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'profile_group_id' => $request->input('profile_group_id'),
                'trigger_condition' => $request->input('trigger_condition'),
                'approval_steps' => $request->input('approval_steps'),
                'auto_approve_after_hours' => $request->input('auto_approve_after_hours'),
                'notify_on_submit' => $request->input('notify_on_submit', true),
                'notify_on_approve' => $request->input('notify_on_approve', true),
                'notify_on_reject' => $request->input('notify_on_reject', true),
                'is_active' => $request->input('is_active', true),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return $this->created($workflow, 'Approval workflow created successfully');
            }

            return redirect()->route('orgs.settings.approval-workflows.show', ['org' => $org, 'workflow' => $workflow->workflow_id])
                ->with('success', 'Approval workflow created successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to create approval workflow: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to create approval workflow'])->withInput();
        }
    }

    /**
     * Display the specified approval workflow.
     */
    public function show(Request $request, string $org, string $workflow)
    {
        $approvalWorkflow = ApprovalWorkflow::where('org_id', $org)
            ->where('workflow_id', $workflow)
            ->with(['profileGroup', 'creator'])
            ->firstOrFail();

        if ($request->wantsJson()) {
            return $this->success($approvalWorkflow, 'Approval workflow retrieved successfully');
        }

        return view('settings.approval-workflows.show', [
            'workflow' => $approvalWorkflow,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for editing the specified approval workflow.
     */
    public function edit(Request $request, string $org, string $workflow)
    {
        $approvalWorkflow = ApprovalWorkflow::where('org_id', $org)
            ->where('workflow_id', $workflow)
            ->firstOrFail();

        $profileGroups = ProfileGroup::where('org_id', $org)->get();
        $users = User::whereHas('orgMemberships', function ($query) use ($org) {
            $query->where('org_id', $org);
        })->get();

        return view('settings.approval-workflows.edit', [
            'workflow' => $approvalWorkflow,
            'currentOrg' => $org,
            'profileGroups' => $profileGroups,
            'users' => $users,
            'triggerConditions' => $this->getTriggerConditions(),
        ]);
    }

    /**
     * Update the specified approval workflow.
     */
    public function update(Request $request, string $org, string $workflow)
    {
        $approvalWorkflow = ApprovalWorkflow::where('org_id', $org)
            ->where('workflow_id', $workflow)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'profile_group_id' => 'nullable|uuid|exists:pgsql.cmis.profile_groups,group_id',
            'trigger_condition' => 'required|string|max:100',
            'approval_steps' => 'required|array|min:1',
            'auto_approve_after_hours' => 'nullable|integer|min:0',
            'notify_on_submit' => 'boolean',
            'notify_on_approve' => 'boolean',
            'notify_on_reject' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $approvalWorkflow->update($request->only([
                'name',
                'description',
                'profile_group_id',
                'trigger_condition',
                'approval_steps',
                'auto_approve_after_hours',
                'notify_on_submit',
                'notify_on_approve',
                'notify_on_reject',
                'is_active',
            ]));

            if ($request->wantsJson()) {
                return $this->success($approvalWorkflow, 'Approval workflow updated successfully');
            }

            return redirect()->route('orgs.settings.approval-workflows.show', ['org' => $org, 'workflow' => $workflow])
                ->with('success', 'Approval workflow updated successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update approval workflow: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update approval workflow'])->withInput();
        }
    }

    /**
     * Remove the specified approval workflow.
     */
    public function destroy(Request $request, string $org, string $workflow)
    {
        $approvalWorkflow = ApprovalWorkflow::where('org_id', $org)
            ->where('workflow_id', $workflow)
            ->firstOrFail();

        try {
            $approvalWorkflow->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Approval workflow deleted successfully');
            }

            return redirect()->route('orgs.settings.approval-workflows.index', ['org' => $org])
                ->with('success', 'Approval workflow deleted successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to delete approval workflow: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete approval workflow']);
        }
    }

    /**
     * Get trigger condition options.
     */
    private function getTriggerConditions(): array
    {
        return [
            'all_posts' => 'All Posts',
            'external_links' => 'Posts with External Links',
            'mentions' => 'Posts with Mentions',
            'hashtags' => 'Posts with Specific Hashtags',
            'first_post' => 'First Post of the Day',
            'high_engagement' => 'High Engagement Content',
            'ad_content' => 'Sponsored/Ad Content',
            'sensitive_topics' => 'Sensitive Topic Keywords',
        ];
    }
}
