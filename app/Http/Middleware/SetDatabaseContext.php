<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ContextNotSetException;
use Symfony\Component\HttpFoundation\Response;

class SetDatabaseContext
{
    /**
     * Handle an incoming request.
     *
     * هذا الـ Middleware يضبط سياق قاعدة البيانات (Database Context) لكل طلب API
     * يستخدم دالة PostgreSQL init_transaction_context لتطبيق RLS
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // استخراج org_id من الرابط
        $orgId = $request->route('org_id');

        if (!$orgId) {
            throw new ContextNotSetException('Organization ID is required');
        }

        // التحقق من تسجيل الدخول
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource'
            ], 401);
        }

        $userId = Auth::id();

        try {
            // بدء معاملة قاعدة البيانات
            DB::beginTransaction();

            // ضبط السياق في قاعدة البيانات باستخدام الدالة الآمنة
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $userId,
                $orgId
            ]);

            Log::debug('Database context initialized', [
                'user_id' => $userId,
                'org_id' => $orgId,
                'route' => $request->route()?->getName(),
            ]);

            // تنفيذ الطلب
            $response = $next($request);

            // تأكيد المعاملة عند النجاح
            DB::commit();

            return $response;

        } catch (\Illuminate\Database\QueryException $e) {
            // التراجع عن المعاملة عند الفشل
            DB::rollBack();

            // التعامل مع أخطاء قاعدة البيانات المحددة
            if (str_contains($e->getMessage(), 'does not belong to org')) {
                Log::warning('User attempted to access unauthorized org', [
                    'user_id' => $userId,
                    'org_id' => $orgId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'Access Denied',
                    'message' => 'You do not have access to this organization'
                ], 403);
            }

            Log::error('Database context initialization failed', [
                'user_id' => $userId,
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Database Error',
                'message' => 'Failed to initialize database context'
            ], 500);

        } catch (\Exception $e) {
            // التراجع عن المعاملة عند أي خطأ آخر
            DB::rollBack();

            Log::error('Unexpected error in SetDatabaseContext', [
                'user_id' => $userId,
                'org_id' => $orgId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        // يمكن إضافة تنظيف أو logging إضافي هنا
    }
}
