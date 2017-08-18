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