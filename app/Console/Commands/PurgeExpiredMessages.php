<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MessageService;
use Illuminate\Console\Command;

class PurgeExpiredMessages extends Command
{
    protected $signature = 'messages:purge-expired';
    protected $description = 'Delete messages that have exceeded their 72-hour TTL';

    public function handle(MessageService $messageService): int
    {
        $count = $messageService->purgeExpired();
        $this->info("Purged {$count} expired messages.");
        return 0;
    }
}