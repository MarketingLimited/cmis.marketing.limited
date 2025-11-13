<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelFormats extends Model
{
    protected $table = 'cmis.channel_formats';
    protected $primaryKey = 'format_id';

    public $timestamps = false;
}
