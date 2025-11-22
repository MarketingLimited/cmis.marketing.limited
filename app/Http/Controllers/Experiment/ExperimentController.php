<?php

namespace App\Http\Controllers\Experiment;

use App\Http\Controllers\Controller;
use App\Models\Experiment\Experiment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Experiment Controller
 *
 * Handles HTTP requests for experiment management
 * Note: Stub implementation
 */
class ExperimentController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of experiments with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('ExperimentController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return response()->json([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for creating a new experiment
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        Log::info('ExperimentController::create called (stub)');

        return response()->json([
            'form_fields' => [],
            'stub' => true
        ], 200);
    }

    /**
     * Store a newly created experiment in database
     *
     * @param Request $request HTTP request with experiment data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('ExperimentController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => 'experiment_stub_' . uniqid(),
            'stub' => true
        ], 201);
    }

    /**
     * Display the specified experiment by ID
     *
     * @param Request $request HTTP request
     * @param string $experiment_id Experiment ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $experiment_id)
    {
        Log::info('ExperimentController::show called (stub)', [
            'experiment_id' => $experiment_id,
        ]);

        return response()->json([
            'data' => ['id' => $experiment_id],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for editing the specified experiment
     *
     * @param Request $request HTTP request
     * @param string $experiment_id Experiment ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $experiment_id)
    {
        Log::info('ExperimentController::edit called (stub)', [
            'experiment_id' => $experiment_id,
        ]);

        return response()->json([
            'form_fields' => ['id' => $experiment_id],
            'stub' => true
        ], 200);
    }

    /**
     * Update the specified experiment in database
     *
     * @param Request $request HTTP request with experiment data
     * @param string $experiment_id Experiment ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $experiment_id)
    {
        Log::info('ExperimentController::update called (stub)', [
            'experiment_id' => $experiment_id,
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $experiment_id,
            'stub' => true
        ], 200);
    }

    /**
     * Remove the specified experiment from database
     *
     * @param Request $request HTTP request
     * @param string $experiment_id Experiment ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $experiment_id)
    {
        Log::info('ExperimentController::destroy called (stub)', [
            'experiment_id' => $experiment_id,
        ]);

        return response()->json([
            'success' => true,
            'stub' => true
        ], 200);
    }
}
