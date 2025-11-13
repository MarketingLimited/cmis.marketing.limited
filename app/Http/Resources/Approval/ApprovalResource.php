<?php

namespace App\Http\Resources\Approval;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'approval_id' => $this->approval_id,
            'post_id' => $this->post_id,
            'status' => $this->status,
            'workflow' => [
                'requested_by' => $this->requested_by,
                'assigned_to' => $this->assigned_to,
                'reviewed_at' => $this->reviewed_at,
            ],
            'feedback' => [
                'comments' => $this->comments,
            ],
            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            // Include post data if loaded
            'post' => $this->whenLoaded('post', function () {
                return [
                    'content' => $this->post->content ?? null,
                    'platform' => $this->post->platform ?? null,
                    'scheduled_for' => $this->post->scheduled_for ?? null,
                ];
            }),
        ];
    }
}
