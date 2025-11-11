<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\KnowledgeAutoLearnJob;

class AutoLearnCommand extends Command
{
    protected $signature = 'cmis:auto-learn';
    protected $description = 'تشغيل دورة التعلم الذاتي يدويًا';

    public function handle(): int
    {
        dispatch(new KnowledgeAutoLearnJob());
        $this->info('✅ تم إطلاق دورة التعلم الذاتي بنجاح.');
        return 0;
    }
}
