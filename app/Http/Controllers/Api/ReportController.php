<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function revenue()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->reportService->revenue(),
        ]);
    }

    public function repairs()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->reportService->repairs(),
        ]);
    }

    public function inventory()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->reportService->inventory(),
        ]);
    }

    public function vehicles()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->reportService->vehicles(),
        ]);
    }

    public function financial()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->reportService->financial(),
        ]);
    }
}
