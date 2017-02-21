<?php 

namespace Kr\Guard;

class NamesConfgHelper
{
        static function getKeys()
    {
        $nodes = self::getKeyNodes();
        return array_keys($nodes);
    }

    static function getKeyThrottles()
    {   
        $nodes = self::getKeyNodes();
        return array_map(function($item){
            return isset($item['throttle']) ? $item['throttle'] : null;
        },$nodes);
    }

    static function getKeyLogs()
    {   
        $nodes = self::getKeyNodes();
        return array_map(function($item){
            return isset($item['log']) ? $item['log'] : null;
        },$nodes);
    }

    static function getKeyNodes()
    {   
        $config = self::getConfig();
        $tmp = [];
        foreach($config as $group){
            $tmp += $group['routes'];
        }
        return $tmp;
    }

    static function getConfig()
    {
        return config('permissions');
    }
    
}