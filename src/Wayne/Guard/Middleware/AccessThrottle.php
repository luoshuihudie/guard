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

        $user = $request->user();
        $group = null;
        if ($user && method_exists($user, 'getGroupIdentifier')) {
            $group = $user->getGroupIdentifier();
        }

        $config = $this->getThrottleConfig($group);
        $decay = $config['decay'];
        $total = $config['total'];

        $user = Auth::user();
        $id = $user->id;
        // 总访问次数
        $totalKey = 'access.throttle.total';
        $theKey = $user->id . ':' . $totalKey;
        if ($total > 0) {
            $totalName ='access.throttle.total';
            $totalKey = $this->getCacheKey($totalName, $user);
            if ($this->limiter->tooManyAttempts($totalKey, $total, $decay)) {
                $handle = $config['trigger'];
                $resp = null;
                if (is_callable($handle)) {
                    $resp = $handle($totalName , $total, $decay);
                } else if(app()->bound($handle)) {
                    $handle = app()->make($handle);
                    $resp = $handle($totalName , $total, $decay);
                }
                return $resp ?: $this->buildResponse($totalName , $total, $config['message']);
            }

            $this->limiter->hit($totalKey, $decay);
        }

        // 单个接口限制访问
        $maxAttempts = 60;
        $routeName = app('router')->currentRouteName();
        $routeKey = $this->getCacheKey($routeName, $user);
        $permissions = \Wayne\Guard\NamesConfigHelper::getKeyThrottles($group);
        if (isset($permissions[$routeName]) && $permissions[$routeName] > 0) {
            $maxAttempts = $permissions[$routeName];
            if ($this->limiter->tooManyAttempts($routeKey, $maxAttempts, $decay)) {
                $resp = null;
                $handle = $config['trigger'];
                if (is_callable($handle)) {
                    $resp = $handle($routeName , $maxAttempts, $decay);
                } else if(app()->bound($handle)) {
                    $handle = app()->make($handle);
                    $resp = $handle($routeName , $maxAttempts, $decay);
                }
                return $resp ?: $this->buildResponse($routeName, $maxAttempts, $config['message']);
            }

            $this->limiter->hit($routeKey, $decay);
        }

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($routeName, $maxAttempts)
        );
    }


    protected function getThrottleConfig($group = null)
    {
        $access = config('access');
        if ($group && isset($access["throttle.{$group}"])) {
            return $access["throttle.{$group}"];
        }
        return $access['throttle'];
    }



    /**
     * Create a 'too many attempts' response.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildResponse($key, $maxAttempts, $message = null)
    {
        $response = new Response($message ?: 'Too Many Attempts.', 429);

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

    protected function getCacheKey($key, $user)
    {
        return $key . ':' . $user->id;
    }
}
