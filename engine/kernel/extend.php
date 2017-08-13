<?php

class Extend extends Genome {

    public static $extend = [];

    public static function exist($input, $fail = false) {
        return Folder::exist(EXTEND . DS . $input, $fail);
    }

    public static function state(...$lot) {
        $id = str_replace('.', '\\', basename(array_shift($lot)));
        $key = array_shift($lot);
        $fail = array_shift($lot) ?: false;
        $folder = (is_array($key) ? $fail : array_shift($lot)) ?: EXTEND;
        $state = $folder . DS . $id . DS . 'lot' . DS . 'state' . DS . 'config.php';
        if (!file_exists($state)) {
            return is_array($key) ? $key : $fail;
        }
        $c = __c2f__(static::class, '_');
        $state = isset(self::$extend[$c][$id]) ? self::$extend[$c][$id] : include $state;
        $state = Hook::NS($c . '.state.' . $id, [$state]);
        if (is_array($key)) {
            return array_replace_recursive($key, $state);
        }
        return isset($key) ? (array_key_exists($key, $state) ? $state[$key] : $fail) : $state;
    }

}