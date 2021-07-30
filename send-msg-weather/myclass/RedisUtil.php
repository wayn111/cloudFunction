<?php


/**
 * redis帮助类
 */
class RedisUtil
{
    private static Redis $_instance;

    public static function getInstance($config): Redis
    {
        if (!empty(self::$_instance)) {
            return self::$_instance;
        }
        self::$_instance = new Redis();
        self::$_instance->connect($config['host'], $config['port']);
        self::$_instance->auth($config['auth']);
        return self::$_instance;
    }

}
