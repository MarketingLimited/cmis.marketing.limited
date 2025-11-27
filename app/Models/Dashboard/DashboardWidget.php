<?php

namespace App\Models\Dashboard;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_dashboard.dashboard_widgets';
    protected $primaryKey = 'widget_id';

    protected $fillable = [
        'widget_id',
        'template_id',
        'org_id',
        'name',
        'widget_type',
        'data_source',
        'query_config',
        'display_config',
        'position_x',
        'position_y',
        'width',
        'height',
        'refresh_interval',
        'cache_duration',
        'filters',
        'aggregation_type',
        'date_range_type',
        'custom_date_range',
        'sort_by',
        'sort_order',
        'limit',
        'show_legend',
        'show_labels',
        'color_scheme',
        'is_visible',
        'is_interactive',
        'drill_down_enabled',
        'export_enabled',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'query_config' => 'array',
        'display_config' => 'array',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'refresh_interval' => 'integer',
        'cache_duration' => 'integer',
        'filters' => 'array',
        'custom_date_range' => 'array',
        'limit' => 'integer',
        'show_legend' => 'boolean',
        'show_labels' => 'boolean',
        'is_visible' => 'boolean',
        'is_interactive' => 'boolean',
        'drill_down_enabled' => 'boolean',
        'export_enabled' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Widget type constants
    public const TYPE_LINE_CHART = 'line_chart';
    public const TYPE_BAR_CHART = 'bar_chart';
    public const TYPE_PIE_CHART = 'pie_chart';
    public const TYPE_AREA_CHART = 'area_chart';
    public const TYPE_TABLE = 'table';
    public const TYPE_KPI_CARD = 'kpi_card';
    public const TYPE_GAUGE = 'gauge';
    public const TYPE_HEATMAP = 'heatmap';
    public const TYPE_FUNNEL = 'funnel';
    public const TYPE_SCATTER = 'scatter';
    public const TYPE_MAP = 'map';
    public const TYPE_CUSTOM = 'custom';

    // Data source constants
    public const SOURCE_CAMPAIGNS = 'campaigns';
    public const SOURCE_AD_SETS = 'ad_sets';
    public const SOURCE_ADS = 'ads';
    public const SOURCE_METRICS = 'metrics';
    public const SOURCE_CONVERSIONS = 'conversions';
    public const SOURCE_BUDGETS = 'budgets';
    public const SOURCE_AUDIENCES = 'audiences';
    public const SOURCE_CUSTOM_QUERY = 'custom_query';

    // Aggregation type constants
    public const AGG_SUM = 'sum';
    public const AGG_AVG = 'average';
    public const AGG_COUNT = 'count';
    public const AGG_MIN = 'min';
    public const AGG_MAX = 'max';
    public const AGG_MEDIAN = 'median';

    // Date range type constants
    public const RANGE_TODAY = 'today';
    public const RANGE_YESTERDAY = 'yesterday';
    public const RANGE_LAST_7_DAYS = 'last_7_days';
    public const RANGE_LAST_30_DAYS = 'last_30_days';
    public const RANGE_THIS_MONTH = 'this_month';
    public const RANGE_LAST_MONTH = 'last_month';
    public const RANGE_THIS_QUARTER = 'this_quarter';
    public const RANGE_THIS_YEAR = 'this_year';
    public const RANGE_CUSTOM = 'custom';

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(DashboardTemplate::class, 'template_id', 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('widget_type', $type);
    }

    public function scopeByDataSource($query, string $source)
    {
        return $query->where('data_source', $source);
    }

    public function scopeInteractive($query)
    {
        return $query->where('is_interactive', true);
    }

    public function scopeOrderedByPosition($query)
    {
        return $query->orderBy('position_y')->orderBy('position_x');
    }

    // Helper Methods
    public function isVisible(): bool
    {
        return $this->is_visible === true;
    }

    public function isInteractive(): bool
    {
        return $this->is_interactive === true;
    }

    public function hasDrillDown(): bool
    {
        return $this->drill_down_enabled === true;
    }

    public function canExport(): bool
    {
        return $this->export_enabled === true;
    }

    public function hide(): bool
    {
        return $this->update(['is_visible' => false]);
    }

    public function show(): bool
    {
        return $this->update(['is_visible' => true]);
    }

    public function updatePosition(int $x, int $y): bool
    {
        return $this->update([
            'position_x' => $x,
            'position_y' => $y,
        ]);
    }

    public function updateSize(int $width, int $height): bool
    {
        return $this->update([
            'width' => $width,
            'height' => $height,
        ]);
    }

    public function updateFilters(array $filters): bool
    {
        return $this->update(['filters' => $filters]);
    }

    public function clearFilters(): bool
    {
        return $this->update(['filters' => []]);
    }

    public function getGridPosition(): array
    {
        return [
            'x' => $this->position_x,
            'y' => $this->position_y,
            'w' => $this->width,
            'h' => $this->height,
        ];
    }

    public function getDateRange(): array
    {
        if ($this->date_range_type === self::RANGE_CUSTOM) {
            return $this->custom_date_range ?? [];
        }

        return match($this->date_range_type) {
            self::RANGE_TODAY => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            self::RANGE_YESTERDAY => [
                'start' => now()->subDay()->startOfDay(),
                'end' => now()->subDay()->endOfDay(),
            ],
            self::RANGE_LAST_7_DAYS => [
                'start' => now()->subDays(7)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            self::RANGE_LAST_30_DAYS => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            self::RANGE_THIS_MONTH => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            self::RANGE_LAST_MONTH => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
            self::RANGE_THIS_QUARTER => [
                'start' => now()->startOfQuarter(),
                'end' => now()->endOfQuarter(),
            ],
            self::RANGE_THIS_YEAR => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            default => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }

    public function shouldRefresh(): bool
    {
        if (!$this->refresh_interval) {
            return false;
        }

        return $this->updated_at->addSeconds($this->refresh_interval)->isPast();
    }

    public function getTypeIcon(): string
    {
        return match($this->widget_type) {
            self::TYPE_LINE_CHART => 'chart-line',
            self::TYPE_BAR_CHART => 'chart-bar',
            self::TYPE_PIE_CHART => 'chart-pie',
            self::TYPE_AREA_CHART => 'chart-area',
            self::TYPE_TABLE => 'table',
            self::TYPE_KPI_CARD => 'square-poll-vertical',
            self::TYPE_GAUGE => 'gauge',
            self::TYPE_HEATMAP => 'fire',
            self::TYPE_FUNNEL => 'filter',
            self::TYPE_SCATTER => 'braille',
            self::TYPE_MAP => 'map',
            default => 'chart-simple',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_LINE_CHART => 'Line Chart',
            self::TYPE_BAR_CHART => 'Bar Chart',
            self::TYPE_PIE_CHART => 'Pie Chart',
            self::TYPE_AREA_CHART => 'Area Chart',
            self::TYPE_TABLE => 'Table',
            self::TYPE_KPI_CARD => 'KPI Card',
            self::TYPE_GAUGE => 'Gauge',
            self::TYPE_HEATMAP => 'Heatmap',
            self::TYPE_FUNNEL => 'Funnel',
            self::TYPE_SCATTER => 'Scatter Plot',
            self::TYPE_MAP => 'Map',
            self::TYPE_CUSTOM => 'Custom',
        ];
    }

    public static function getDataSourceOptions(): array
    {
        return [
            self::SOURCE_CAMPAIGNS => 'Campaigns',
            self::SOURCE_AD_SETS => 'Ad Sets',
            self::SOURCE_ADS => 'Ads',
            self::SOURCE_METRICS => 'Metrics',
            self::SOURCE_CONVERSIONS => 'Conversions',
            self::SOURCE_BUDGETS => 'Budgets',
            self::SOURCE_AUDIENCES => 'Audiences',
            self::SOURCE_CUSTOM_QUERY => 'Custom Query',
        ];
    }

    public static function getAggregationOptions(): array
    {
        return [
            self::AGG_SUM => 'Sum',
            self::AGG_AVG => 'Average',
            self::AGG_COUNT => 'Count',
            self::AGG_MIN => 'Minimum',
            self::AGG_MAX => 'Maximum',
            self::AGG_MEDIAN => 'Median',
        ];
    }

    public static function getDateRangeOptions(): array
    {
        return [
            self::RANGE_TODAY => 'Today',
            self::RANGE_YESTERDAY => 'Yesterday',
            self::RANGE_LAST_7_DAYS => 'Last 7 Days',
            self::RANGE_LAST_30_DAYS => 'Last 30 Days',
            self::RANGE_THIS_MONTH => 'This Month',
            self::RANGE_LAST_MONTH => 'Last Month',
            self::RANGE_THIS_QUARTER => 'This Quarter',
            self::RANGE_THIS_YEAR => 'This Year',
            self::RANGE_CUSTOM => 'Custom Range',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'template_id' => 'required|uuid|exists:cmis_dashboard.dashboard_templates,template_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'widget_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'data_source' => 'required|in:' . implode(',', array_keys(self::getDataSourceOptions())),
            'query_config' => 'nullable|array',
            'display_config' => 'nullable|array',
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
            'width' => 'required|integer|min:1|max:12',
            'height' => 'required|integer|min:1|max:12',
            'refresh_interval' => 'nullable|integer|min:5',
            'cache_duration' => 'nullable|integer|min:60',
            'filters' => 'nullable|array',
            'aggregation_type' => 'nullable|in:' . implode(',', array_keys(self::getAggregationOptions())),
            'date_range_type' => 'required|in:' . implode(',', array_keys(self::getDateRangeOptions())),
            'custom_date_range' => 'nullable|array',
            'sort_by' => 'nullable|string|max:255',
            'sort_order' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:1000',
            'show_legend' => 'nullable|boolean',
            'show_labels' => 'nullable|boolean',
            'color_scheme' => 'nullable|string|max:50',
            'is_visible' => 'nullable|boolean',
            'is_interactive' => 'nullable|boolean',
            'drill_down_enabled' => 'nullable|boolean',
            'export_enabled' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'display_config' => 'sometimes|array',
            'position_x' => 'sometimes|integer|min:0',
            'position_y' => 'sometimes|integer|min:0',
            'width' => 'sometimes|integer|min:1|max:12',
            'height' => 'sometimes|integer|min:1|max:12',
            'filters' => 'sometimes|array',
            'is_visible' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'template_id.required' => 'Dashboard template is required',
            'org_id.required' => 'Organization is required',
            'name.required' => 'Widget name is required',
            'widget_type.required' => 'Widget type is required',
            'data_source.required' => 'Data source is required',
            'position_x.required' => 'X position is required',
            'position_y.required' => 'Y position is required',
            'width.required' => 'Width is required',
            'width.max' => 'Width cannot exceed 12 columns',
            'height.required' => 'Height is required',
            'date_range_type.required' => 'Date range type is required',
        ];
    }
}
