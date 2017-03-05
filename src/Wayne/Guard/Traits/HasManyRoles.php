<?php namespace Wayne\Guard\Traits;

trait HasManyRoles
{

    function isSuper()
    {
        $permissions = $this->getMergedPermissions();
        return isset($permissions['super']) && $permissions['super'];
    }

    function hasAccess($key)
    {
        $permissions = $this->getMergedPermissions();
        if (isset($permissions['super']) && $permissions['super']) {
            return true;
        }
        return isset($permissions[$key]) && $permissions[$key];
    }

    function getPermissions()
    {
        if ($this->isSuper()) {
            return $this->getFullPermissions();
        }
        return $this->getMergedPermissions();
    }

    function getFullPermissions()
    {
        $keys = \Wayne\Guard\NamesConfigHelper::getKeys();
        return array_fill_keys($keys, 1);
    }

    function getSelfPermissions()
    {
        $permission = $this->permissions ?: [];
        $permission = array_map(function ($item) {
            return intval($item);
        }, $permission);
        return $permission;
    }

    function getRolePermissions($roles = [])
    {
        $selfRoles = $this->roles;
        if (!empty($roles)) {
            $selfRoles = $selfRoles->filter(function ($item) use ($roles) {
                return in_array($item->id, $roles);
            });
        }
        $permissions = [];
        $selfRoles->each(function ($item) use (&$permissions) {
            if (!empty($item->permissions)) {
                $permission = array_map(function ($item) {
                    return intval($item);
                }, $item->permissions ?: []);
                $permissions = array_merge($permissions, $permission);
            }
        });
        return $permissions;
    }

    function getMergedPermissions()
    {
        $selfPermissions  = $this->getSelfPermissions();
        $groupPermissions = $this->getRolePermissions();
        $merged           = array_merge($groupPermissions, $selfPermissions);
        $merged           = array_filter($merged, function ($item) {
            return intval($item) !== -1;
        });
        return $merged;
    }
}
