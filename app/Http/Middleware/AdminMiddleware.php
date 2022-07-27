<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $roleId = Auth::user()->role_id;
        if($roleId == 1){
            return $next($request);
        } else {
            $msg=__("api_string.not_authorized");
            return response()->json(["status"=>false,'statusCode'=>401,"message"=>$msg]);
        }
    }
}
