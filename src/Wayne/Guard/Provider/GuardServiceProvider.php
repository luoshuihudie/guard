<?php namespace Wayne\Guard\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class GuardServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->publishes([
            __DIR__.'/../config/access.php' => config_path('access.php'),
            __DIR__.'/../config/permissions.home.php' => config_path('permissions.home.php')
        ], 'config');

        $this->registerPolicies($gate);

        if (config('auth.authorizate.switch', false)) {
            $gate->before(function ($user, $ability) {
                if ($user->isSuper()) {
                    return true;
                }
            });

            $keys = \Wayne\Guard\NamesConfigHelper::getKeys();
            foreach ($keys as $key) {
                $gate->define($key, function ($user) use ($key) {
                    return $user->hasAccess($key);
                });
            }
            $gate->before(function ($user, $ability) use ($keys) {
                if (!in_array($ability, $keys)) {
                    // 未定义的直接放行
                    return true;
                }
            });
        }

        // 注册行为日志
        app()->instance('access.log', new \Wayne\Guard\Services\AccessLog);
    }
}
