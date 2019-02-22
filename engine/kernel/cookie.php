<?php

class Cookie extends Genome {

    const config = [
        'expires' => '1 day',
        'path' => '/',
        'domain' => "",
        'secure' => false,
        'httponly' => false
    ];

    public static $config = self::config;

    private static function k($key) {
        return '_' . dechex(crc32(static::class . ':' . $key));
    }

    private static function v($value) {
        return json_decode(base64_decode($value), true);
    }

    private static function x($value) {
        return base64_encode(json_encode($value));
    }

    public static function get(string $key = null) {
        if (isset($key)) {
            return self::v($_COOKIE[self::k($key)] ?? 'bnVsbA==');
        }
        $out = [];
        foreach ($_COOKIE as $k => $v) {
            $out[$k] = e(strpos($k, '_') === 0 ? self::v($v) : $v);
        }
        return $out;
    }

    public static function reset($key = null) {
        if (is_array($key)) {
            foreach ($key as $v) {
                self::reset($v);
            }
        } else if (isset($key)) {
            $key = self::k($key);
            setcookie($key, null, -1);
            setcookie($key, null, -1, '/');
        } else {
            foreach ($_COOKIE as $k => $v) {
                setcookie($k, null, -1);
                setcookie($k, null, -1, '/');
            }
        }
    }

    public static function set(string $key, $value = "", $expires = '1 day') {
        if (!is_array($expires)) {
            $expires = ['expires' => $expires];
        }
        $c = array_values(extend(self::$config, $expires));
        if (is_string($c[0])) {
            $c[0] = (int) (strtotime($c[0], $t = time()) - $t);
        }
        $c[0] += time();
        setcookie(self::k($key), self::x($value), ...$c);
    }

}