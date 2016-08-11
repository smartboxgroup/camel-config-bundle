<?php

namespace Smartbox\Integration\CamelConfigBundle\Tests\App;

use Smartbox\CoreBundle\Utils\Cache\CacheServiceInterface;

class ArrayCacheService implements CacheServiceInterface{

    protected $cache = [];

    /**
     * @param $key
     * @param mixed $value
     * @param null $expireTTL
     *
     * @return bool
     */
    public function set($key, $value, $expireTTL = null)
    {
        error_log("SET $key");
        $this->cache[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function get($key)
    {
        error_log("GET $key");
        return @$this->cache[$key];
    }

    /**
     * @param $key
     * @param $ttlLimit
     *
     * @return bool
     */
    public function exists($key, $ttlLimit = null)
    {
        error_log("EXISTS $key");
        return array_key_exists($key,$this->cache);
    }
}