<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends BaseModel
{
    protected $table = 'cmis.channels';

    protected $primaryKey = 'channel_id';

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'channel_id',
        'code',
        'name',
        'constraints',
    ];

    protected $casts = [
        'channel_id' => 'int',
        'constraints' => 'array',
    ];

    public function formats(): HasMany
    {
        return $this->hasMany(ChannelFormat::class, 'channel_id', 'channel_id');
}
}
