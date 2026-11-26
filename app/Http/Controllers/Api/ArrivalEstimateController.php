<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stop;
use App\Services\ArrivalEstimateService;
use Illuminate\Http\JsonResponse;

/**
 * RF-30: contrato JSON de llegadas estimadas para una parada.
 */
class ArrivalEstimateController extends Controller
{
    public function __construct(private readonly ArrivalEstimateService $estimates) {}

    public function index(Stop $stop): JsonResponse
    {
        return response()->json($this->estimates->forStop($stop));
    }
}
