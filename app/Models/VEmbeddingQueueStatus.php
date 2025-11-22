<?php

namespace App\Models;

use App\Models\BaseModel;

class VEmbeddingQueueStatus extends BaseModel
{
    protected $table = 'cmis_knowledge.v_embedding_queue_status';
    protected $guarded = ['*'];
    public $timestamps = false;
}
