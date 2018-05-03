<?php

namespace Wayne\Guard;

class NamesConfigHelper
{
    public static function getKeys()
    {
        $nodes = self::getKeyNodes();
        return array_keys($nodes);
    }

    public static function getKeyPermissions()
    {
        $nodes = self::getKeyNodes();
        $nodes = array_filter($nodes, function ($item) {
            return !isset($item['limit-on']) || $item['limit-on'];
        }, ARRAY_FILTER_USE_BOTH);
        return array_keys($nodes);
    }

    public static function getKeyThrottles($group = null)
    {
        $nodes = self::getKeyNodes();
        return array_map(function ($item) use ($group) {
            if ($group && isset($item["throttle.{$group}"])) {
                return $item["throttle.{$group}"];
            }
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
