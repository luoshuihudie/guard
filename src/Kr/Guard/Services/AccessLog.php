<?php

namespace Kr\Guard\Services;

use Kr\Guard\NamesConfigHelper;

class AccessLog
{
    protected $bindings = [];
    protected $logs = [];

    public function __construct()
    {
        $permissions = \Kr\Guard\NamesConfigHelper::getConfig();;
        $this->logs  = collect($permissions)
            ->flatMap(function ($item) {
                return $item['routes'];
            })->map(function ($item) {
            return array_where($item, function ($value, $key) {
                return starts_with($key, 'log.');
            });
        })->filter();
    }

    public function get($name)
    {
        if (isset($this->bindings[$name])) {
            return $this->bindings[$name];
        }
        return array_get(app(), $name);
    }

    public function set($name, $value)
    {
        $this->bindings[$name] = $value;
    }

    public function log($callback)
    {
        if (!is_callable($callback)) {
            return false;
        }

        $routeName = app('router')->currentRouteName();
        if (!isset($this->logs[$routeName])) {
            return false;
        }
        $logs = $this->logs[$routeName];
        foreach ($logs as $key => $log) {
            list($name, $type) = explode('.', $key);
            $callback($routeName, $type, $this->format($log));
        }
    }

    public function format($log)
    {
        preg_match_all('/\{\{(.*?)\}\}/', $log, $matches);
        $i = 0;
        foreach ($matches[0] as $replace) {
            $key = $matches[1][$i++];
            $log = str_replace($replace, $this->get($key), $log);
        }
        return $log;
    }
}
