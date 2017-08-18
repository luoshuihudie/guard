<?php

namespace Wayne\Guard;

class NamesConfigHelper
{
    public static function getKeys()
    {
        $nodes = self::getKeyNodes();
        return array_keys($nodes);
    }

    public static function getKeyThrottles()
    {
        $nodes = self::getKeyNodes();
        return array_map(function ($item) {
            return isset($item['throttle']) ? $item['throttle'] : null;
        }, $nodes);
    }

    public static function getKeyLogs()
    {
        $nodes = self::getKeyNodes();
        return array_map(function ($item) {
            return isset($item['log']) ? $item['log'] : null;
        }, $nodes);
    }

    public static function getKeyNodes()
    {
        $config = self::getConfig();
        $tmp    = [];
        foreach ($config as $group) {
            $tmp += $group['routes'];
        }
        return $tmp;
    }

    public static function getConfig()
    {
        return config('permissions', []);
    }

}
