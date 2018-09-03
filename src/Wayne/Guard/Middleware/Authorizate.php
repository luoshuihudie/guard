<?php namespace Wayne\Guard\Middleware;

use Closure;
use Gate;
use Illuminate\Auth\AuthenticationException;

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
        if (config('auth.authorizate.switch', false) && Gate::denies($routeName)) {
            throw new AuthenticationException('您的权限不足！');
        }
        return $next($request);
    }
}
