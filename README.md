# guard
基于 laravel 的后台权限控制相关组件

## require

laraverl ~ '5.2.*'

## 说明

在配置文件 app.php 的 providers 段添加如下代码

```
Wayne\Guard\Providers\GuardServiceProvider::class,
```

然后执行命令
```
php artisan vendor:publish --provider='Wayne\Guard\Providers\GuardServiceProvider' --tag='config'
```

## 整体逻辑


权限控制：将 http resource 对应的【路由别名】作为权限与用户关联，用户访问一个http request时先将用户能访问的所有路由别名全取出来，然后判断当前路由是否在可访问列表了，如果不在则403返回否则直接放行 

频次限制：设置每个路由单位时间内对应访问的次数，在缓存中设置相应键值，每访问一次该用户对应的键值自增1，超过设置阈值执行相关报警逻辑

访问日志：设置每个路由对应的访问日志，当该请求完成时，自动分析日志模板依次 app('access.logger')，app() 上获取绑定数据,然后交由 handle 处理

## 权限控制

## 权限处理相关修改
app/Http/Kernel.php中的代码应该类似于下面这样
```
<?php
namespace App\Http;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];
    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\Before::class,
            \App\Http\Middleware\After::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];
    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,


        'guard.throttle' => \Wayne\Guard\Middleware\AccessThrottle::class,
        'guard.logger' => \Wayne\Guard\Middleware\AccessLogger::class,
        'guard.auth' => \Wayne\Guard\Middleware\Authenticate::class,
        'guard.permission' => \Wayne\Guard\Middleware\Authorizate::class,
    ];
}
```

其中 
```
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\Before::class,
            \App\Http\Middleware\After::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
```
和
```
        'guard.throttle' => \Wayne\Guard\Middleware\AccessThrottle::class,
        'guard.logger' => \Wayne\Guard\Middleware\AccessLogger::class,
        'guard.auth' => \Wayne\Guard\Middleware\Authenticate::class,
        'guard.permission' => \Wayne\Guard\Middleware\Authorizate::class,
```
是权限控制必要的部分


#### 记录日志