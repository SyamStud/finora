<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->reports->dashboard(
            $request->user(),
            $request->string('period')->toString() ?: 'month',
            $request->string('from')->toString() ?: null,
            $request->string('to')->toString() ?: null,
        );

        $data['recent_transactions'] = TransactionResource::collection($data['recent_transactions']);

        return response()->json($data);
    }
}
