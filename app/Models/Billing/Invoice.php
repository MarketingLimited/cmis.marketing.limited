<?php

namespace App\Models\Billing;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Invoice extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.invoices';
    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'invoice_id',
        'org_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'due_date',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
