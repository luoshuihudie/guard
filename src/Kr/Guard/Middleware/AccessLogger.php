<?php namespace Kr\Guard\Middleware;

use Closure,Gate;

class AccessLogger
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
        $response = $next($request);

        $handle = config('access.logger.handle');
        if (!is_callable($handle) && app()->bound($handle)) {
            $handle = app()->make($handle);
        }

        if (is_callable($handle)) {
            app('access.log')->log($handle);
        }

        return $response;
    }
}