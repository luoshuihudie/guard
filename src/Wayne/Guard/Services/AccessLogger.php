<?php

namespace Wayne\Guard\Services;

use Wayne\Guard\NamesConfigHelper;

class AccessLogger
{
    protected $bindings = [];
    protected $logs = [];

    public function __construct()
    {
        $permissions = \Wayne\Guard\NamesConfigHelper::getConfig();
        $this->logs  = collect($permissions)
            ->flatMap(function ($item) {
                return $item['routes'];
            })->map(function ($item) {
                return array_filter($item, function ($value, $key) {
                    return $this->startsWith($key, 'log.');
                }, ARRAY_FILTER_USE_BOTH);
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

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}
