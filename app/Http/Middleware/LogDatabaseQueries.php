<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogDatabaseQueries
{
    /**
     * Handle an incoming request.
     *
     * يسجل جميع استعلامات قاعدة البيانات للتطوير والتصحيح
     * تفعيله فقط في بيئة التطوير أو عند الحاجة
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تفعيل الـ logging فقط في بيئة development
        if (config('app.debug') && config('database.log_queries', false)) {
            $startTime = microtime(true);

            // تفعيل Query Logging
            DB::enableQueryLog();

            // تنفيذ الطلب
            $response = $next($request);

            // الحصول على الاستعلامات المنفذة
            $queries = DB::getQueryLog();
            $executionTime = microtime(true) - $startTime;

            // تسجيل الاستعلامات
            if (count($queries) > 0) {
                Log::channel('database')->info('Database Queries', [
                    'route' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                    'org_id' => $request->route('org_id'),
                    'query_count' => count($queries),
                    'execution_time' => round($executionTime * 1000, 2) . 'ms',
                    'queries' => array_map(function ($query) {
                        return [
                            'query' => $query['query'],
                            'bindings' => $query['bindings'],
                            'time' => $query['time'] . 'ms',
                        ];
                    }, $queries),
                ]);

                // تحذير عند وجود استعلامات كثيرة (N+1 problem)
                if (count($queries) > 50) {
                    Log::channel('database')->warning('High query count detected', [
                        'route' => $request->route()?->getName(),
                        'query_count' => count($queries),
                        'hint' => 'Possible N+1 query problem',
                    ]);
                }

                // تحذير عند بطء الاستعلامات
                if ($executionTime > 1) {
                    Log::channel('database')->warning('Slow queries detected', [
                        'route' => $request->route()?->getName(),
                        'execution_time' => round($executionTime * 1000, 2) . 'ms',
                    ]);
                }
            }

            return $response;
        }

        return $next($request);
    }
}
