<?php

namespace App\Events\Content;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentPublishedEvent
{
    use Dispatchable, SerializesModels;

    public $content;

    public function __construct($content)
    {
        $this->content = $content;
    }
}
