<?php

namespace Paysera\Component\RestClientCommon\Util;

class ConfigHandler
{
    const CONFIG_NAMESPACE = 'paysera';
    const KEY_AUTHENTICATION = 'authentication';

    private static $whitelistedConfiguration = [
        'proxy',
        'cookies',
        'headers',
    ];

    /**
     * @param array $config
     * @param string $parameter
     * @return mixed|null
     */
    public static function getParameter(array $config, $parameter)
    {
        if (isset($config[self::CONFIG_NAMESPACE]) && array_key_exists($parameter, $config[self::CONFIG_NAMESPACE])) {
            return $config[self::CONFIG_NAMESPACE][$parameter];
        }

        return null;
    }

    /**
     * @param array $config
     * @param string $type
     * @return array|null
     */
    public static function getAuthentication(array $config, $type)
    {
        $auth = self::getParameter($config, self::KEY_AUTHENTICATION);
        if ($auth === null || !isset($auth[$type])) {
            return null;
        }

        return $auth[$type];
    }

    public static function setAuthentication(array &$config, array $auth)
    {
        $config[self::CONFIG_NAMESPACE][self::KEY_AUTHENTICATION] = $auth;
    }

    public static function appendConfiguration(array &$config, array $options)
    {
        $optionsToAppend = [];

        foreach ($options as $option => $value) {
            if (in_array($option, self::$whitelistedConfiguration, true)) {
                $optionsToAppend[$option] = $value;
            }
        }

        $config = array_merge($config, $optionsToAppend);
    }
}
