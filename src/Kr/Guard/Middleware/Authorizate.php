<?php namespace Kr\Guard\Middleware;

use Closure,Gate;

class Authorizate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $routeName = app('router')->currentRouteName();
        if(Gate::denies($routeName)){
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'code' => 403,
                    'msg' => config('auth.response.403','您的权限不足，请联系管理员获取更多访问权限。')
                ])->setStatusCode(403);
            } else {
                return response(
                    config('auth.response.403','您的权限不足，请联系管理员获取更多访问权限。')
                    , 403
                );
            }
        }
        return $next($request);
    }
}