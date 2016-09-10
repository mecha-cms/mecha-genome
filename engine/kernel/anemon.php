<?php

class Anemon extends Genome {

    protected $bucket = [];
    protected $separator = "";
    protected $i = 0;

    // Prevent `$x` exceeds the value of `$min` and `$max`
    public static function edge($x, $min = 0, $max = 9999) {
        if ($x < $min) return $min;
        if ($x > $max) return $max;
        return $x;
    }

    // Set array value recursively
    public static function set(&$input, $k, $v = null) {
        $kk = explode('.', $k);
        while (count($kk) > 1) {
            $k = array_shift($kk);
            if (!array_key_exists($k, $input)) {
                $input[$k] = [];
            }
            $input =& $input[$k];
        }
        $input[array_shift($kk)] = $v;
    }

    // Get array value recursively
    public static function get(&$input, $k = null, $fail = false) {
        if ($k === null) return $input;
        $kk = explode('.', $k);
        foreach ($kk as $v) {
            if (!is_array($input) || !array_key_exists($v, $input)) {
                return $fail;
            }
            $input =& $input[$v];
        }
        return $input;
    }

    public static function reset(&$input, $k) {
        $kk = explode('.', $k);
        while (count($k) > 1) {
            $k = array_shift($kk);
            if (array_key_exists($k, $input)) {
                $input =& $input[$k];
            }
        }
        if (is_array($input) && array_key_exists($v = array_shift($kk), $input)) {
            unset($input[$v]);
        }
    }

    public static function extend(&$a, $b) {
        $a = array_replace_recursive((array) $a, (array) $b);
        return $a;
    }

    public static function concat(&$a, $b) {
        $a = array_merge_recursive((array) $a, (array) $b);
        return $a;
    }

    public static function eat($group) {
        return new self($group);
    }

    public function vomit($k = null, $fail = false) {
        return $this->get($this->bucket, $k, $fail);
    }

    public function shake($fn = null) {
        if (is_callable($fn)) {
            $this->bucket = call_user_func($fn, $this->bucket);
        } else {
            // <http://php.net/manual/en/function.shuffle.php#94697>
            $k = array_keys($this->bucket);
            $v = [];
            shuffle($k);
            foreach ($k as $kk) {
                $v[$kk] = $this->bucket[$kk];
            }
            $this->bucket = $v;
            unset($k, $v);
        }
        return $this;
    }

    public function filter($fn) {
        $this->bucket = array_filter($this->bucket, $fn, ARRAY_FILTER_USE_BOTH);
        return $this;
    }

    public function sort($order = 'ASC', $key = null, $prsv_key = false, $null = X) {
        if (!is_null($key)) {
            $before = $after = [];
            if (!empty($this->bucket)) {
                foreach ($this->bucket as $k => $v) {
                    $v = (array) $v;
                    if (array_key_exists($key, $v)) {
                        $before[$k] = $v[$key];
                    } elseif ($null !== X) {
                        $before[$k] = $null;
                        $this->bucket[$k][$key] = $null;
                    }
                }
                $order === 'ASC' ? asort($before) : arsort($before);
                foreach ($before as $k => $v) {
                    $after[$k] = $this->bucket[$k];
                }
            }
            $this->bucket = $after;
            unset($before, $after);
        } else {
            $this->bucket = (array) $this->bucket;
            $order === 'ASC' ? asort($this->bucket) : arsort($this->bucket);
        }
        if (!$prsv_key) {
            $this->bucket = array_values($this->bucket);
        }
        return $this;
    }

    public static function walk($group, $fn = null) {
        if (is_callable($fn)) {
            foreach (a($group) as $k => &$v) {
                $v = is_array($v) ? array_merge($v, self::walk($v, $fn)) : call_user_func($fn, $v, $k);
            }
            unset($v);
            return $group;
        }
        return self::eat($group);
    }

    public static function alter($input, $replace = [], $fail = null) {
        // return the `$replace[$input]` value if exist
        // or the `$fail` value if `$replace[$input]` does not exist
        // or the `$input` value if `$fail` is `null`
        return array_key_exists($input, $replace) ? $replace[$input] : ($fail ?? $input);
    }

    // Move to next array index
    public function next($skip = 0) {
        $this->i = $this->edge($this->i + 1 + $skip, 0, $this->count() - 1);
        return $this;
    }

    // Move to previous array index
    public function prev($skip = 0) {
        $this->i = self::edge($this->i - 1 - $skip, 0, $this->count() - 1);
        return $this;
    }

    // Alias for `Anemon::prev()`
    public function previous(...$lot) {
        return call_user_func_array([$this, 'prev'], $lot);
    }

    // Move to `$index` array index
    public function to($index) {
        $this->i = is_int($index) ? $index : $this->index($index, $index);
        return $this;
    }

    // Insert `$food` before current array index
    public function before($food, $key = null) {
        $key = $key ?? $this->i;
        $this->bucket = array_slice($this->bucket, 0, $this->i, true) + [$key => $food] + array_slice($this->bucket, $this->i, null, true);
        $this->i = self::edge($this->i - 1, 0, $this->count() - 1);
        return $this;
    }

    // Insert `$food` after current array index
    public function after($food, $key = null) {
        $key = $key ?? $this->i + 1;
        $this->bucket = array_slice($this->bucket, 0, $this->i + 1, true) + [$key => $food] + array_slice($this->bucket, $this->i + 1, null, true);
        $this->i = self::edge($this->i + 1, 0, $this->count() - 1);
        return $this;
    }

    // Replace current array index value with `$food`
    public function replace($food) {
        $i = 0;
        foreach ($this->bucket as $k => $v) {
            if ($i === $this->i) {
                $this->bucket[$k] = $food;
                break;
            }
            $i++;
        }
        return $this;
    }

    // Append `$food` to array
    public function append($food, $key = null) {
        $this->i = $this->count() - 1;
        return $this->after($food, $key);
    }

    // Prepend `$food` to array
    public function prepend($food, $key = null) {
        $this->i = 0;
        return $this->before($food, $key);
    }

    // Get first array value
    public function first() {
        $this->i = 0;
        return reset($this->bucket);
    }

    // Get last array value
    public function last() {
        $this->i = $this->count() - 1;
        return end($this->bucket);
    }

    // Get current array value
    public function current($fail = false) {
        $i = 0;
        foreach ($this->bucket as $k => $v) {
            if ($i === $this->i) {
                return $this->bucket[$k];
            }
            $i++;
        }
        return $fail;
    }

    // Get array length
    public function count($deep = false) {
        return count($this->bucket, $deep ? COUNT_RECURSIVE : COUNT_NORMAL);
    }

    // Get array key by position
    public function key($index, $fail = false) {
        $array = array_keys($this->bucket);
        return $array[$index] ?? $fail;
    }

    // Get position by array key
    public function index($key, $fail = false) {
        return array_search($key, array_keys($this->bucket)) ?? $fail;
    }

    // Generate chunk(s) of array
    public function chunk($chunk = 25, $output = null, $fail = []) {
        $chunk = array_chunk($this->bucket, $chunk, true);
        return $output === null ? $chunk : $chunk[$output] ?? $fail;
    }

    public function swap($a, $b = null) {
        return array_column($this->bucket, $a, $b);
    }

    public function join($s = ', ') {
        return $this->__invoke($s);
    }

    public function __construct($group = [], $s = ', ') {
        $this->bucket = $group;
        $this->separator = $s;
    }

    public function __get($key) {
        return $this->bucket[$key] ?? false;
    }

    public function __set($key, $value = null) {
        $this->bucket[$key] = $value;
    }

    public function __toString() {
        return $this->__invoke($this->separator);
    }

    public function __invoke($s = ', ', $filter = true) {
        return implode($s, $filter ? $this->filter(function($v, $k) {
            return strpos($k, '__') !== 0;
        })->vomit() : $this->bucket);
    }

}