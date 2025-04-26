<?php

// namespace App\Http\Middleware;

// use Closure;
// use Tymon\JWTAuth\Facades\JWTAuth;
// use Exception;

// class AdminAuthMiddleware {
//     public function handle($request, Closure $next) {
//         try {
//             if (!$admin = JWTAuth::parseToken()->authenticate()) {
//                 return response()->json(['error' => 'Unauthorized'], 401);
//             }
//         } catch (Exception $e) {
//             return response()->json(['error' => 'Token is invalid'], 401);
//         }

//         return $next($request);
//     }
// }

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $admin = Auth::guard('admin')->user(); // ✅ Use 'admin' guard

            if (!$admin) {
                return response()->json(['error' => 'Unauthorized. Admin authentication required.'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid or missing token.'], 401);
        }

        return $next($request);
    }
}
