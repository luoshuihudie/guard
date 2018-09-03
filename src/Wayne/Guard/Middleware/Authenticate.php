<?php namespace Wayne\Guard\Middleware;

use Auth;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


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
        // if (Auth::driver($guard)->guest()) {
        if (Auth::guard($guard)->guest()) {// ~5.2
            $redirect_url = $request->get('redirect_to') ?: config('sso.uri-success-skip');
            session(['redirect_url' => $redirect_url]);
            throw new UnauthorizedHttpException('您登录已过期，请重新登录。');
        }

        $user = Auth::user();
        app()->instance('login', $user);

        // 如果已登录
        if (!$user->activated) {
            throw new UnauthorizedHttpException('您的账户已被禁用，请联系管理员获取更多访问权限。');
        }
        return $next($request);
    }
}
