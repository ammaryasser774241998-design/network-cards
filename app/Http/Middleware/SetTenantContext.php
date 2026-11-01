<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->is_active === false, 403);
        TenantContext::set($request->user()?->tenant_id);

        return $next($request);
    }
}
