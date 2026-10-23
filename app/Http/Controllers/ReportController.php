<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reported_user_id' => 'required|integer|exists:users,id',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'message_id' => 'nullable|integer|exists:messages,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $report = $this->reportService->report(
                $request->user()->id,
                $data['reported_user_id'],
                $data['conversation_id'] ?? null,
                $data['message_id'] ?? null,
                $data['reason'] ?? null,
                $request->ip(),
                $request->userAgent()
            );
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $report->id, 'report_count' => $report->reportedUser?->report_count],
        ], 201);
    }
}