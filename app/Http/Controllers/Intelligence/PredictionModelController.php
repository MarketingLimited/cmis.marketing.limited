<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Intelligence\PredictionModel;
use App\Services\Intelligence\PredictionModelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PredictionModelController extends Controller
{
    use ApiResponse;

    protected PredictionModelService $modelService;

    public function __construct(PredictionModelService $modelService)
    {
        $this->modelService = $modelService;
    }

    /**
     * Display a listing of prediction models
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $models = PredictionModel::where('org_id', $orgId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->algorithm, fn($q) => $q->byAlgorithm($request->algorithm))
            ->when($request->metric, fn($q) => $q->byMetric($request->metric))
            ->when($request->active_only, fn($q) => $q->active())
            ->with(['creator'])
            ->latest('last_trained_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($models, 'Prediction models retrieved successfully');
        }

        return view('intelligence.models.index', compact('models'));
    }

    /**
     * Show the form for creating a new model
     */
    public function create()
    {
        return view('intelligence.models.create');
    }

    /**
     * Store a newly created model
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), PredictionModel::createRules(), PredictionModel::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $model = $this->modelService->createModel($request->all());

        if ($request->expectsJson()) {
            return $this->created($model, 'Prediction model created successfully');
        }

        return redirect()->route('models.show', $model->model_id)
            ->with('success', 'Prediction model created successfully');
    }

    /**
     * Display the specified model
     */
    public function show(string $id)
    {
        $model = PredictionModel::with(['creator'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($model, 'Prediction model retrieved successfully');
        }

        return view('intelligence.models.show', compact('model'));
    }

    /**
     * Show the form for editing the specified model
     */
    public function edit(string $id)
    {
        $model = PredictionModel::findOrFail($id);

        return view('intelligence.models.edit', compact('model'));
    }

    /**
     * Update the specified model
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), PredictionModel::updateRules(), PredictionModel::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $model = PredictionModel::findOrFail($id);
        $model->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($model, 'Prediction model updated successfully');
        }

        return redirect()->route('models.show', $model->model_id)
            ->with('success', 'Prediction model updated successfully');
    }

    /**
     * Remove the specified model
     */
    public function destroy(string $id)
    {
        $model = PredictionModel::findOrFail($id);
        $model->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Prediction model deleted successfully');
        }

        return redirect()->route('models.index')
            ->with('success', 'Prediction model deleted successfully');
    }

    /**
     * Train a prediction model
     */
    public function train(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'training_data' => 'nullable|array',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
            'validation_split' => 'nullable|numeric|min:0.1|max:0.5',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $model = PredictionModel::findOrFail($id);

        $result = $this->modelService->trainModel(
            $model,
            $request->training_data,
            $request->date_from,
            $request->date_to,
            $request->validation_split ?? 0.2
        );

        if ($request->expectsJson()) {
            return $this->success($result, 'Model training completed successfully');
        }

        return redirect()->route('models.show', $model->model_id)
            ->with('success', 'Model training completed successfully');
    }

    /**
     * Evaluate model performance
     */
    public function evaluate(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'test_data' => 'nullable|array',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $model = PredictionModel::findOrFail($id);

        $evaluation = $this->modelService->evaluateModel(
            $model,
            $request->test_data,
            $request->date_from,
            $request->date_to
        );

        return $this->success($evaluation, 'Model evaluation completed successfully');
    }

    /**
     * Retrain model with new data
     */
    public function retrain(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'incremental' => 'nullable|boolean',
            'hyperparameters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $model = PredictionModel::findOrFail($id);

        $result = $this->modelService->retrainModel(
            $model,
            $request->incremental ?? false,
            $request->hyperparameters
        );

        if ($request->expectsJson()) {
            return $this->success($result, 'Model retraining completed successfully');
        }

        return redirect()->route('models.show', $model->model_id)
            ->with('success', 'Model retraining completed successfully');
    }

    /**
     * Activate a model
     */
    public function activate(string $id)
    {
        $model = PredictionModel::findOrFail($id);
        $model->activate();

        if (request()->expectsJson()) {
            return $this->success($model, 'Model activated successfully');
        }

        return redirect()->route('models.show', $model->model_id)
            ->with('success', 'Model activated successfully');
    }

    /**
     * Deactivate a model
     */
    public function deactivate(string $id)
    {
        $model = PredictionModel::findOrFail($id);
        $model->deactivate();

        if (request()->expectsJson()) {
            return $this->success($model, 'Model deactivated successfully');
        }

        return redirect()->route('models.show', $model->model_id)
            ->with('success', 'Model deactivated successfully');
    }

    /**
     * Archive a model
     */
    public function archive(string $id)
    {
        $model = PredictionModel::findOrFail($id);
        $model->archive();

        if (request()->expectsJson()) {
            return $this->success($model, 'Model archived successfully');
        }

        return redirect()->route('models.index')
            ->with('success', 'Model archived successfully');
    }

    /**
     * Get model accuracy comparison
     */
    public function accuracy(Request $request)
    {
        $orgId = session('current_org_id');

        $validator = Validator::make($request->all(), [
            'metric' => 'nullable|string',
            'algorithm' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $comparison = $this->modelService->compareModelAccuracy(
            $orgId,
            $request->metric,
            $request->algorithm
        );

        return $this->success($comparison, 'Model accuracy comparison retrieved successfully');
    }

    /**
     * Get model analytics dashboard data
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $analytics = $this->modelService->getAnalytics($orgId);

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Model analytics retrieved successfully');
        }

        return view('intelligence.models.analytics', compact('analytics'));
    }

    /**
     * Get models due for retraining
     */
    public function dueForRetraining(Request $request)
    {
        $orgId = session('current_org_id');

        $models = PredictionModel::where('org_id', $orgId)
            ->needsRetraining()
            ->with(['creator'])
            ->get();

        return $this->success($models, 'Models due for retraining retrieved successfully');
    }

    /**
     * Bulk retrain models
     */
    public function bulkRetrain(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'model_ids' => 'required|array|min:1',
            'model_ids.*' => 'uuid|exists:cmis_intelligence.prediction_models,model_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $results = $this->modelService->bulkRetrainModels($request->model_ids);

        return $this->success($results, 'Bulk retraining completed successfully');
    }

    /**
     * Export model configuration
     */
    public function export(string $id)
    {
        $model = PredictionModel::findOrFail($id);

        $export = $this->modelService->exportModel($model);

        return response()->json($export)
            ->header('Content-Disposition', 'attachment; filename="model-' . $model->model_id . '.json"');
    }

    /**
     * Import model configuration
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'model_config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $model = $this->modelService->importModel($request->model_config);

        if ($request->expectsJson()) {
            return $this->created($model, 'Model imported successfully');
        }

        return redirect()->route('models.show', $model->model_id)
            ->with('success', 'Model imported successfully');
    }
}
