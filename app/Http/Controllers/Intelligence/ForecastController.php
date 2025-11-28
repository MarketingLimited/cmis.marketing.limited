<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Intelligence\Forecast;
use App\Services\Intelligence\ForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForecastController extends Controller
{
    use ApiResponse;

    protected ForecastService $forecastService;

    public function __construct(ForecastService $forecastService)
    {
        $this->forecastService = $forecastService;
    }

    /**
     * Display a listing of forecasts
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $forecasts = Forecast::where('org_id', $orgId)
            ->when($request->metric, fn($q) => $q->byMetric($request->metric))
            ->when($request->campaign_id, fn($q) => $q->where('campaign_id', $request->campaign_id))
            ->when($request->date_from, fn($q) => $q->where('forecast_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->where('forecast_date', '<=', $request->date_to))
            ->with(['predictionModel', 'campaign', 'creator'])
            ->latest('forecast_date')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($forecasts, 'Forecasts retrieved successfully');
        }

        return view('intelligence.forecasts.index', compact('forecasts'));
    }

    /**
     * Show the form for creating a new forecast
     */
    public function create()
    {
        return view('intelligence.forecasts.create');
    }

    /**
     * Store a newly created forecast
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Forecast::createRules(), Forecast::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $forecast = $this->forecastService->createForecast($request->all());

        if ($request->expectsJson()) {
            return $this->created($forecast, 'Forecast created successfully');
        }

        return redirect()->route('forecasts.show', $forecast->forecast_id)
            ->with('success', __('intelligence.created_success'));
    }

    /**
     * Display the specified forecast
     */
    public function show(string $id)
    {
        $forecast = Forecast::with(['predictionModel', 'campaign', 'creator'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($forecast, 'Forecast retrieved successfully');
        }

        return view('intelligence.forecasts.show', compact('forecast'));
    }

    /**
     * Show the form for editing the specified forecast
     */
    public function edit(string $id)
    {
        $forecast = Forecast::findOrFail($id);

        return view('intelligence.forecasts.edit', compact('forecast'));
    }

    /**
     * Update the specified forecast
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), Forecast::updateRules(), Forecast::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $forecast = Forecast::findOrFail($id);
        $forecast->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($forecast, 'Forecast updated successfully');
        }

        return redirect()->route('forecasts.show', $forecast->forecast_id)
            ->with('success', __('intelligence.updated_success'));
    }

    /**
     * Remove the specified forecast
     */
    public function destroy(string $id)
    {
        $forecast = Forecast::findOrFail($id);
        $forecast->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Forecast deleted successfully');
        }

        return redirect()->route('forecasts.index')
            ->with('success', __('intelligence.deleted_success'));
    }

    /**
     * Generate forecasts for a campaign
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|uuid|exists:cmis.campaigns,campaign_id',
            'metrics' => 'required|array|min:1',
            'forecast_horizon' => 'required|integer|min:1|max:90',
            'model_id' => 'nullable|uuid|exists:cmis_intelligence.prediction_models,model_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $forecasts = $this->forecastService->generateForecasts(
            $request->campaign_id,
            $request->metrics,
            $request->forecast_horizon,
            $request->model_id
        );

        return $this->success($forecasts, 'Forecasts generated successfully');
    }

    /**
     * Get forecast accuracy comparison
     */
    public function accuracy(Request $request)
    {
        $orgId = session('current_org_id');

        $validator = Validator::make($request->all(), [
            'campaign_id' => 'nullable|uuid|exists:cmis.campaigns,campaign_id',
            'metric' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $accuracy = $this->forecastService->getAccuracyReport(
            $orgId,
            $request->campaign_id,
            $request->metric,
            $request->date_from,
            $request->date_to
        );

        return $this->success($accuracy, 'Forecast accuracy report retrieved successfully');
    }

    /**
     * Get forecast analytics dashboard data
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $analytics = $this->forecastService->getAnalytics($orgId);

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Forecast analytics retrieved successfully');
        }

        return view('intelligence.forecasts.analytics', compact('analytics'));
    }

    /**
     * Record actual values for forecasts
     */
    public function recordActuals(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'actuals' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $forecast = Forecast::findOrFail($id);
        $forecast->recordActuals($request->actuals);

        return $this->success($forecast, 'Actuals recorded successfully');
    }
}
