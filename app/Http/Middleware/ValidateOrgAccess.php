<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Exceptions\OrgAccessDeniedException;
use Symfony\Component\HttpFoundation\Response;

class ValidateOrgAccess
{
    /**
     * Handle an incoming request.
     *
     * يتحقق من صلاحيات المستخدم للوصول إلى الشركة المطلوبة
     * يعمل قبل SetDatabaseContext للتحقق السريع
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من تسجيل الدخول
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource'
            ], 401);
        }

        $userId = Auth::id();
        $orgId = $request->route('org_id');

        if (!$orgId) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Organization ID is required'
            ], 400);
        }

        try {
            // التحقق من العضوية النشطة في الشركة
            $hasAccess = DB::table('cmis.user_orgs')
                ->where('user_id', $userId)
                ->where('org_id', $orgId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->exists();

            if (!$hasAccess) {
                Log::warning('User attempted to access unauthorized organization', [
                    'user_id' => $userId,
                    'org_id' => $orgId,
                    'ip' => $request->ip(),
                    'route' => $request->route()?->getName(),
                ]);

                throw new OrgAccessDeniedException(
                    "Access denied to organization {$orgId}",
                    $orgId
                );
            }

            // تحديث آخر وصول (اختياري - يمكن تعطيله للأداء)
            DB::table('cmis.user_orgs')
                ->where('user_id', $userId)
                ->where('org_id', $orgId)
                ->update(['last_accessed' => now()]);

            Log::debug('Organization access validated', [
                'user_id' => $userId,
                'org_id' => $orgId,
            ]);

            return $next($request);

        } catch (OrgAccessDeniedException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error validating organization access', [
                'user_id' => $userId,
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Server Error',
                'message' => 'Failed to validate organization access'
            ], 500);
        }
    }
}
