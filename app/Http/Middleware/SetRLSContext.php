<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetRLSContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->current_org_id) {
            DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
                $user->user_id,
                $user->current_org_id
            ]);
        }

        $response = $next($request);

        if ($user) {
            DB::statement('SELECT cmis.clear_transaction_context()');
        }

        return $response;
    }
}
