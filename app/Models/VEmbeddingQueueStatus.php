<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VEmbeddingQueueStatus extends Model
{
    protected $table = 'cmis_knowledge.v_embedding_queue_status';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
