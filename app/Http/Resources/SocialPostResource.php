<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'post_id' => $this->post_id,
            'org_id' => $this->org_id,
            'campaign_id' => $this->campaign_id,
            'account_id' => $this->account_id,
            'platform' => $this->platform,
            'post_text' => $this->post_text,
            'media_urls' => $this->media_urls,
            'scheduled_at' => $this->scheduled_at,
            'published_at' => $this->published_at,
            'status' => $this->status,
            'external_id' => $this->external_id,
            'metrics' => [
                'likes' => $this->likes_count ?? 0,
                'comments' => $this->comments_count ?? 0,
                'shares' => $this->shares_count ?? 0,
                'reach' => $this->reach ?? 0,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
