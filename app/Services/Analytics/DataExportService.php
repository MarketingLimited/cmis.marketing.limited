<?php

namespace App\Services\Analytics;

use App\Models\Analytics\DataExportConfig;
use App\Models\Analytics\DataExportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Data Export Service (Phase 14)
 *
 * Handles data exports in multiple formats with delivery options
 */
class DataExportService
{
    /**
     * Execute export configuration
     */
    public function executeExport(DataExportConfig $config): DataExportLog
    {
        $log = DataExportLog::create([
            'config_id' => $config->config_id,
            'org_id' => $config->org_id,
            'started_at' => now(),
            'status' => 'processing',
            'format' => $config->format
        ]);

        try {
            // Fetch data based on export type
            $data = $this->fetchExportData($config);

            // Generate file in requested format
            $filePath = $this->generateFile($data, $config->format, $config->name);
            $fileSize = Storage::size($filePath);

            // Deliver via configured method
            if ($config->delivery_method !== 'download') {
                $this->deliverExport($filePath, $config);
            }

            $log->markCompleted(count($data), $fileSize, $filePath);
            $config->markExported();

            return $log;
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch data for export based on configuration
     */
    protected function fetchExportData(DataExportConfig $config): array
    {
        $dataConfig = $config->data_config;

        return match ($config->export_type) {
            'analytics' => $this->fetchAnalyticsData($config->org_id, $dataConfig),
            'campaigns' => $this->fetchCampaignsData($config->org_id, $dataConfig),
            'metrics' => $this->fetchMetricsData($config->org_id, $dataConfig),
            'custom' => $this->fetchCustomData($config->org_id, $dataConfig),
            default => throw new \InvalidArgumentException("Unsupported export type: {$config->export_type}")
        };
    }

    /**
     * Fetch analytics data
     */
    protected function fetchAnalyticsData(string $orgId, array $config): array
    {
        $query = DB::table('cmis.campaigns as c')
            ->where('c.org_id', $orgId)
            ->select([
                'c.campaign_id',
                'c.name',
                'c.status',
                'c.start_date',
                'c.end_date',
                'c.budget',
                'c.created_at'
            ]);

        if (isset($config['date_range'])) {
            $query->whereBetween('c.start_date', [
                $config['date_range']['start'],
                $config['date_range']['end']
            ]);
        }

        if (isset($config['status'])) {
            $query->whereIn('c.status', (array) $config['status']);
        }

        return $query->get()->toArray();
    }

    /**
     * Fetch campaigns data
     */
    protected function fetchCampaignsData(string $orgId, array $config): array
    {
        return DB::table('cmis.campaigns')
            ->where('org_id', $orgId)
            ->get()
            ->toArray();
    }

    /**
     * Fetch metrics data
     */
    protected function fetchMetricsData(string $orgId, array $config): array
    {
        // Simplified - would query actual metrics tables
        return [];
    }

    /**
     * Fetch custom query data
     */
    protected function fetchCustomData(string $orgId, array $config): array
    {
        // Execute custom query with safety checks
        if (!isset($config['query'])) {
            throw new \InvalidArgumentException('Custom export requires query configuration');
        }

        // Validate query safety
        $this->validateCustomQuery($config['query']);

        return DB::select($config['query'], ['org_id' => $orgId]);
    }

    /**
     * Validate custom query for safety
     */
    protected function validateCustomQuery(string $query): void
    {
        $forbidden = ['drop', 'delete', 'truncate', 'alter', 'create', 'insert', 'update'];

        foreach ($forbidden as $keyword) {
            if (stripos($query, $keyword) !== false) {
                throw new \RuntimeException("Query contains forbidden keyword: {$keyword}");
            }
        }
    }

    /**
     * Generate export file in specified format
     */
    protected function generateFile(array $data, string $format, string $baseName): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $filename = "exports/{$baseName}_{$timestamp}";

        return match ($format) {
            'json' => $this->generateJSON($data, $filename),
            'csv' => $this->generateCSV($data, $filename),
            'xlsx' => $this->generateXLSX($data, $filename),
            'parquet' => $this->generateParquet($data, $filename),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    /**
     * Generate JSON file
     */
    protected function generateJSON(array $data, string $filename): string
    {
        $path = "{$filename}.json";
        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT));
        return $path;
    }

    /**
     * Generate CSV file
     */
    protected function generateCSV(array $data, string $filename): string
    {
        if (empty($data)) {
            $path = "{$filename}.csv";
            Storage::put($path, '');
            return $path;
        }

        $csv = fopen('php://temp', 'w+');

        // Headers
        $headers = array_keys((array) $data[0]);
        fputcsv($csv, $headers);

        // Data rows
        foreach ($data as $row) {
            fputcsv($csv, (array) $row);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $path = "{$filename}.csv";
        Storage::put($path, $content);
        return $path;
    }

    /**
     * Generate XLSX file (simplified - would use PhpSpreadsheet in production)
     */
    protected function generateXLSX(array $data, string $filename): string
    {
        // For now, generate CSV and rename (in production, use PhpSpreadsheet)
        return $this->generateCSV($data, $filename);
    }

    /**
     * Generate Parquet file (simplified)
     */
    protected function generateParquet(array $data, string $filename): string
    {
        // In production, would use parquet-php or similar library
        return $this->generateJSON($data, $filename);
    }

    /**
     * Deliver export via configured method
     */
    protected function deliverExport(string $filePath, DataExportConfig $config): void
    {
        $deliveryConfig = $config->delivery_config;

        match ($config->delivery_method) {
            'webhook' => $this->deliverViaWebhook($filePath, $deliveryConfig),
            'sftp' => $this->deliverViaSFTP($filePath, $deliveryConfig),
            's3' => $this->deliverViaS3($filePath, $deliveryConfig),
            default => null
        };
    }

    /**
     * Deliver via webhook
     */
    protected function deliverViaWebhook(string $filePath, array $config): void
    {
        $url = $config['url'] ?? null;
        if (!$url) {
            throw new \RuntimeException('Webhook URL not configured');
        }

        $content = Storage::get($filePath);

        $response = \Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-CMIS-Export' => 'true'
        ])->post($url, [
            'file_name' => basename($filePath),
            'content' => base64_encode($content),
            'timestamp' => now()->toIso8601String()
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Webhook delivery failed: HTTP {$response->status()}");
        }
    }

    /**
     * Deliver via SFTP
     */
    protected function deliverViaSFTP(string $filePath, array $config): void
    {
        // In production, would use phpseclib or Laravel's SFTP driver
        throw new \RuntimeException('SFTP delivery not yet implemented');
    }

    /**
     * Deliver via S3
     */
    protected function deliverViaS3(string $filePath, array $config): void
    {
        $bucket = $config['bucket'] ?? null;
        if (!$bucket) {
            throw new \RuntimeException('S3 bucket not configured');
        }

        // In production, would use AWS SDK
        throw new \RuntimeException('S3 delivery not yet implemented');
    }

    /**
     * Manual export (one-time)
     */
    public function manualExport(
        string $orgId,
        string $exportType,
        string $format,
        array $dataConfig = []
    ): DataExportLog {
        $tempConfig = new DataExportConfig([
            'org_id' => $orgId,
            'name' => 'manual_export',
            'export_type' => $exportType,
            'format' => $format,
            'delivery_method' => 'download',
            'data_config' => $dataConfig,
            'delivery_config' => []
        ]);

        return $this->executeExport($tempConfig);
    }
}
