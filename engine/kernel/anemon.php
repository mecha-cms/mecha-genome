<?php

class Anemon extends Genome implements \ArrayAccess {

    public $lot = [];
    public $separator = "";
    public $i = 0;

    // Create list of namespace step(s)
    public static function step($in, string $NS = '.', int $dir = 1) {
        if (is_string($in) && strpos($in, $NS) !== false) {
            $in = explode($NS, trim($in, $NS));
            $a = $dir === -1 ? array_pop($in) : array_shift($in);
            $out = [$a];
            if ($dir === -1) {
                while ($b = array_pop($in)) {
                    $a = $b . $NS . $a;
                    array_unshift($out, $a);
                }
            } else {
                while ($b = array_shift($in)) {
                    $a .= $NS . $b;
                    array_unshift($out, $a);
                }
            }
            return $out;
        }
        return (array) $in;
    }

    // Set array value recursively
    public static function set(array &$array, string $key, $value = null, string $NS = '.') {
        $keys = explode($NS, str_replace('\\' . $NS, X, $key));
        while (count($keys) > 1) {
            $key = str_replace(X, $NS, array_shift($keys));
            if (!array_key_exists($key, $array)) {
                $array[$key] = [];
            }
            $array =& $array[$key];
        }
        return ($array[array_shift($keys)] = $value);
    }

    // Get array value recursively
    public static function get(array &$array, string $key, $fail = false, string $NS = '.') {
        $keys = explode($NS, str_replace('\\' . $NS, X, $key));
        foreach ($keys as $value) {
            $value = str_replace(X, $NS, $value);
            if (!is_array($array) || !array_key_exists($value, $array)) {
                return $fail;
            }
            $array =& $array[$value];
        }
        return $array;
    }

    // Remove array value recursively
    public static function reset(array &$array, string $key, string $NS = '.') {
        $keys = explode($NS, str_replace('\\' . $NS, X, $key));
        while (count($keys) > 1) {
            $key = str_replace(X, $NS, array_shift($keys));
            if (array_key_exists($key, $array)) {
                $array =& $array[$key];
            }
        }
        if (is_array($array) && array_key_exists($value = array_shift($keys), $array)) {
            unset($array[$value]);
        }
        return $array;
    }

    public static function eat(array $array) {
        return new static($array);
    }

    public function vomit($key = null, $fail = false) {
        if (isset($key)) {
            return self::get($this->lot, $key, $fail);
        }
        return $this->lot;
    }

    // Randomize array order
    public function shake($preserve_key = true) {
        if (is_callable($preserve_key)) {
            // `$preserve_key` as `$fn`
            $this->lot = call_user_func($preserve_key, $this->lot);
        } else {
            // <http://php.net/manual/en/function.shuffle.php#94697>
            if ($preserve_key) {
                $k = array_keys($this->lot);
                $v = [];
                shuffle($k);
                foreach ($k as $kk) {
                    $v[$kk] = $this->lot[$kk];
                }
                $this->lot = $v;
                unset($k, $v);
            } else {
                shuffle($this->lot);
            }
        }
        return $this;
    }

    // Sort array value: `1` for “asc” and `-1` for “desc”
    public function sort($sort = 1, $preserve_key = false) {
        if (is_array($sort) && isset($sort[1])) {
            $before = $after = [];
            if (!empty($this->lot)) {
                foreach ($this->lot as $k => $v) {
                    $v = (array) $v;
                    if (array_key_exists($sort[1], $v)) {
                        $before[$k] = $v[$sort[1]];
                    } else if (!is_bool($preserve_key)) {
                        $before[$k] = (string) $preserve_key;
                        $this->lot[$k][$sort[1]] = (string) $preserve_key;
                    }
                }
                $sort[0] === -1 ? arsort($before) : asort($before);
                foreach ($before as $k => $v) {
                    $after[$k] = $this->lot[$k];
                }
            }
            $this->lot = $after;
            unset($before, $after);
        } else {
            if (is_array($sort)) {
                $sort = $sort[0];
            }
            $this->lot = (array) $this->lot;
            $sort === -1 ? arsort($this->lot) : asort($this->lot);
        }
        if ($preserve_key === false) {
            $this->lot = array_values($this->lot);
        }
        return $this;
    }

    public static function alter($key, array $data = [], $fail = null) {
        // Return `$data[$key]` value if exist
        // or `$fail` value if `$data[$key]` does not exist
        // or `$key` value if `$fail` is `null`
        return array_key_exists((string) $key, $data) ? $data[$key] : ($fail ?? $key);
    }

    // Move to next array index
    public function next(int $skip = 0) {
        $this->i = b($this->i + 1 + $skip, 0, $this->count() - 1);
        return $this;
    }

    // Move to previous array index
    public function previous(int $skip = 0) {
        $this->i = b($this->i - 1 - $skip, 0, $this->count() - 1);
        return $this;
    }

    // Move to `$index` array index
    public function to($index) {
        $this->i = is_int($index) ? $index : $this->index($index, $index);
        return $this;
    }

    // Insert `$value` before current array index
    public function before($value, $key = null) {
        $key = $key ?: $this->i;
        $this->lot = array_slice($this->lot, 0, $this->i, true) + [$key => $value] + array_slice($this->lot, $this->i, null, true);
        $this->i = b($this->i - 1, 0, $this->count() - 1);
        return $this;
    }

    // Insert `$value` after current array index
    public function after($value, $key = null) {
        $key = $key ?: $this->i + 1;
        $this->lot = array_slice($this->lot, 0, $this->i + 1, true) + [$key => $value] + array_slice($this->lot, $this->i + 1, null, true);
        $this->i = b($this->i + 1, 0, $this->count() - 1);
        return $this;
    }

    // Replace current array index value with `$value`
    public function replace($value) {
        $i = 0;
        foreach ($this->lot as $k => $v) {
            if ($i === $this->i) {
                $this->lot[$k] = $value;
                break;
            }
            ++$i;
        }
        return $this;
    }

    // Append `$value` to array
    public function append($value, $key = null) {
        $this->i = $this->count() - 1;
        return $this->after($value, $key);
    }

    // Prepend `$value` to array
    public function prepend($value, $key = null) {
        $this->i = 0;
        return $this->before($value, $key);
    }

    // Get first array value
    public function first() {
        $this->i = 0;
        return reset($this->lot);
    }

    // Get last array value
    public function last() {
        $this->i = $this->count() - 1;
        return end($this->lot);
    }

    // Get current array value
    public function current($fail = false) {
        $i = 0;
        foreach ($this->lot as $k => $v) {
            if ($i === $this->i) {
                return $this->lot[$k];
            }
            ++$i;
        }
        return $fail;
    }

    // Get array length
    public function count($deep = false) {
        return count($this->lot, $deep ? COUNT_RECURSIVE : COUNT_NORMAL);
    }

    // Get array key by position
    public function key(int $index, $fail = false) {
        $array = array_keys($this->lot);
        return array_key_exists($index, $array) ? $array[$index] : $fail;
    }

    // Get position by array key
    public function index(string $key, $fail = false) {
        $search = array_search($key, array_keys($this->lot));
        return $search !== false ? $search : $fail;
    }

    // Generate chunk(s) of array
    public function chunk(int $chunk = 5, $index = null, $fail = [], $preserve_key = false) {
        $chunks = array_chunk(fn\is\anemon($this->lot) ? (array) $this->lot : [], $chunk, $preserve_key);
        return !isset($index) ? $chunks : (array_key_exists($index, $chunks) ? $chunks[$index] : $fail);
    }

    public function offsetSet($i, $value) {
        if (!isset($i)) {
            $this->lot[] = $value;
        } else {
            $this->lot[$i] = $value;
        }
    }

    public function offsetExists($i) {
        return isset($this->lot[$i]);
    }

    public function offsetUnset($i) {
        unset($this->lot[$i]);
    }

    public function offsetGet($i) {
        return $this->lot[$i] ?? null;
    }

    public function __construct(array $array = [], string $separator = ', ') {
        $this->lot = $array;
        $this->separator = $separator;
        parent::__construct();
    }

    public function __set($key, $value = null) {
        $this->lot[$key] = $value;
    }

    public function __get($key) {
        return array_key_exists($key, $this->lot) ? $this->lot[$key] : null;
    }

    // Fix case for `isset($a->key)` or `!empty($a->key)`
    public function __isset($key) {
        return !!$this->__get($key);
    }

    public function __unset($key) {
        unset($this->lot[$key]);
    }

    public function __toString() {
        return $this->__invoke($this->separator);
    }

    public function __invoke(string $s = ', ', $filter = true) {
        return implode($s, $filter ? is($this->lot, function($v, $k) {
            // Ignore `null` value and item with key prefixed by a `_`
            return isset($v) && strpos($k, '_') !== 0;
        }) : $this->lot);
    }

}