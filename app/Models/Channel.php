<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis.channels';

    protected $primaryKey = 'channel_id';

    public $incrementing = false;

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
