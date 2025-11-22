<?php

namespace App\Models\Creative;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContentPlan extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.content_plans';
    protected $primaryKey = 'plan_id';
                /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');

    /**
     * Get the content items
     */
    public function items()
    {
        return $this->hasMany(ContentItem::class, 'plan_id', 'plan_id');

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    /**
     * Scope active plans
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
}
