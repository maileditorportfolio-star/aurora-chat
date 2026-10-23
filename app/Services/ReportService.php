<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{
    public function __construct(
        private ShortIdService $shortId,
    ) {
    }

    public function report(int $reporterId, int $reportedUserId, ?int $conversationId, ?int $messageId, ?string $reason, ?string $ip, ?string $userAgent): Report
    {
        return DB::transaction(function () use ($reporterId, $reportedUserId, $conversationId, $messageId, $reason, $ip, $userAgent) {
            $report = Report::create([
                'reporter_id' => $reporterId,
                'reported_user_id' => $reportedUserId,
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
                'reason' => $reason,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            $user = User::lockForUpdate()->find($reportedUserId);
            if (!$user) {
                return $report;
            }

            $newCount = ($user->report_count ?? 0) + 1;
            $threshold = (int) config('app.report_block_threshold', 50);

            if ($newCount >= $threshold && !$user->is_blocked) {
                $user->update([
                    'is_blocked' => true,
                    'blocked_at' => now(),
                ]);
                Log::warning('User blocked due to reports', [
                    'user_id' => $user->id,
                    'short_id' => $user->short_id,
                    'report_count' => $newCount,
                ]);
            } else {
                $user->update(['report_count' => $newCount]);
            }

            return $report;
        });
    }

    public function getReportedUsers(int $limit = 50)
    {
        return User::where('report_count', '>', 0)
            ->withCount('reportsReceived as report_count')
            ->orderBy('report_count', 'desc')
            ->limit($limit)
            ->get();
    }
}