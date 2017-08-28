<?php namespace Wayne\Guard\Middleware;

use Closure, Auth;
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

        $user = Auth::user();
        $id = $user->id;
        // 总访问次数
        $totalKey = 'access.throttle.total';
        $theKey = $user->id . ':' . $totalKey;
        if ($total > 0) {
            if ($this->limiter->tooManyAttempts($theKey , $total, $decayMinutes)) {
                $handle = config('access.throttle.handle');
                if (is_callable($handle)) {
                    $response = $handle($totalKey , $total, $decayMinutes);
                } else if(app()->bound($handle)) {
                    $handle = app()->make($handle);
                    $response = $handle($totalKey , $total, $decayMinutes);
                }
                return $response ?: $this->buildResponse($theKey , $total);
            }

            $this->limiter->hit($theKey, $decayMinutes);
        }

        // 单个接口限制访问
        $key = app('router')->currentRouteName();
        $theKey = $user->id . ':' . $key;
        $permissions = \Wayne\Guard\NamesConfigHelper::getKeyThrottles();
        if (isset($permissions[$key]) && $permissions[$key] > 0) {
            $maxAttempts = $permissions[$key];
            if ($this->limiter->tooManyAttempts($theKey, $maxAttempts, $decayMinutes)) {
                $handle = config('access.throttle.handle');
                if (is_callable($handle)) {
                    $response = $handle($key, $maxAttempts, $decayMinutes);
                } else if(app()->bound($handle)) {
                    $handle = app()->make($handle);
                    $response = $handle($key, $maxAttempts, $decayMinutes);
                }
                return $response ?: $this->buildResponse($key, $maxAttempts);
            }

            $this->limiter->hit($theKey, $decayMinutes);
        }

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($theKey, $maxAttempts)
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
