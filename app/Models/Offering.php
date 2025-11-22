<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Offering extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.offerings';

    protected $primaryKey = 'offering_id';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'kind',
        'name',
        'description',
    ];

    protected $casts = [
        'offering_id' => 'string',
        'org_id' => 'string',
        'created_at' => 'datetime',
    ];

    

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(
            Campaign::class,
            'cmis.campaign_offerings',
            'offering_id',
            'campaign_id'
}
