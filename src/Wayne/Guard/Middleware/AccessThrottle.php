<?php namespace Wayne\Guard\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AccessThrottle
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new request throttler.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts
     * @param  float|int  $decayMinutes
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        $maxAttempts = 60;
        $decayMinutes = config('access.throttle.decay', 1);
        $total = config('access.throttle.total', 0);

        // 总访问次数
        if ($total > 0) {
            $totalKey = 'access.throttle.total';
            if ($this->limiter->tooManyAttempts($totalKey , $total, $decayMinutes)) {
                $handle = config('access.throttle.handle');
                if (is_callable($handle)) {
                    $handle($totalKey , $total, $decayMinutes);
                } else if(app()->bound($handle)) {
                    $handle = app()->make($handle);
                    $handle($totalKey , $total, $decayMinutes);
                }
                return $this->buildResponse($totalKey , $total);
            }

            $this->limiter->hit($totalKey, $decayMinutes);
        }

        // 单个接口限制访问
        $key = app('router')->currentRouteName();
        $permissions = config('permissions');
        if (isset($permissions[$key]) 
            && isset($permissions[$key]['throttle']) 
            && $permissions[$key]['throttle'] >0) {
            $maxAttempts = $permissions[$key]['throttle'];
            if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
                $handle = config('access.throttle.handle');
                if (is_callable($handle)) {
                    $handle($key, $total, $decayMinutes);
                } else if(app()->bound($handle)) {
                    $handle = app()->make($handle);
                    $handle($key, $total, $decayMinutes);
                }
                return $this->buildResponse($key, $maxAttempts);
            }

            $this->limiter->hit($key, $decayMinutes);
        }

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Create a 'too many attempts' response.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildResponse($key, $maxAttempts)
    {
        $response = new Response('Too Many Attempts.', 429);

        $retryAfter = $this->limiter->availableIn($key);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );
    }

    /**
     * Add the limit header information to the given response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if (! is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = Carbon::now()->getTimestamp() + $retryAfter;
        }

        $response->headers->add($headers);

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int|null  $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (! is_null($retryAfter)) {
            return 0;
        }

        return $this->limiter->retriesLeft($key, $maxAttempts);
    }
}
