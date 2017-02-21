<?php namespace Kr\Guard\Middleware;

use Auth;
use Closure;

class Authenticate
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

        // 如果未登录
        if (Auth::driver($guard)->guest()) {
            // if (Auth::guard($guard)->guest()) {// ~5.2
            $redirect_url = $request->get('redirect_to') ?: config('sso.uri-success-skip');
            session(['redirect_url' => $redirect_url]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'code'     => 401,
                    'msg'      => config('auth.response.401', '您登录已过期，请重新登录。'),
                    'redirect' => config('auth.gateway', '/login'),
                ], 401);
            } else {
                return config('auth.gateway', '/login');
            }
        }

        $user = Auth::user();
        app()->instance('user', $user);

        // 如果已登录
        if (!$user->activated) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'code' => 401,
                    'msg'  => config('auth.response.401', '您的账户已被禁用，请联系管理员获取更多访问权限。'),
                ])->setStatusCode(401);
            } else {
                return response(config('auth.response.401', '您的账户已被禁用，请联系管理员获取更多访问权限。'), 401);
            }
        }
        return $next($request);
    }
}
