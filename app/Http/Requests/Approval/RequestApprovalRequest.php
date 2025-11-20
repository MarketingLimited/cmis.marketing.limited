<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class RequestApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Log::info('RequestApprovalRequest::authorize called (stub) - User approval check not yet implemented');
        // Stub implementation - Check if user can request approval for this post
        // return $this->user()->can('request-approval', $this->input('post_id'));
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'post_id' => 'required|uuid',
            'assigned_to' => 'nullable|uuid'
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'post_id.required' => 'Post ID is required',
            'post_id.uuid' => 'Post ID must be a valid UUID',
            'assigned_to.uuid' => 'Assigned user must be a valid UUID'
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'post_id' => 'post',
            'assigned_to' => 'reviewer'
        ];
    }
}
