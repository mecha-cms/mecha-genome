<?php

class Folder extends Genome {

    public $exist;
    public $path;
    public $value;

    public function __construct($path = null) {
        $this->value[0] = null;
        if ($path && is_string($path) && 0 === strpos($path, ROOT)) {
            $path = strtr($path, '/', DS);
            if (!stream_resolve_include_path($path)) {
                mkdir($path, 0775, true); // Create an empty folder
            }
            $this->path = is_dir($path) ? (realpath($path) ?: $path) : null;
        }
        $this->exist = !!$this->path;
    }

    public function __toString() {
        return $this->exist ? $this->path : "";
    }

    public function URL() {
        return $this->exist ? To::URL($this->path) : null;
    }

    public function _seal() {
        return $this->exist ? fileperms($this->path) : null;
    }

    public function _size() {
        if ($this->exist) {
            // Empty folder
            if (0 === q(g($this->path))) {
                return 0;
            }
            // Scan all file(s) and count the file size
            $size = 0;
            foreach ($this->get(1, true) as $k => $v) {
                $size += filesize($k);
            }
            return $size;
        }
        return null;
    }

    // Set folder permission
    public function seal($i = null) {
        if (isset($i) && $this->exist) {
            $i = is_string($i) ? octdec($i) : $i;
            // Return `$i` on success, `null` on error
            return chmod($this->path, $i) ? $i : null;
        }
        if (null !== ($i = $this->_seal())) {
            return substr(sprintf('%o', $i), -4);
        }
        // Return `false` if file does not exist
        return false;
    }

    public function copy(string $to) {
        $out = [[]];
        if ($this->exist && $path = $this->path) {
            if (!is_dir($to)) {
                mkdir($to, 0775, true);
            }
            $out[1] = [];
            foreach (g($path, null, true) as $k => $v) {
                $out[0][] = $k;
                if (is_file($v = $to . str_replace($path, "", $k))) {
                    // Return `false` if file already exists
                    $out[1][] = false;
                } else {
                    // Return `$v` on success, `null` on error
                    $out[1][] = copy($path, $v) ? $v : null;
                }
            }
        }
        $this->value[1] = $out;
        return $this;
    }

    public function get($x = null, $deep = 0) {
        return y($this->stream($x, $deep));
    }

    public function move(string $to) {
        $out = [[]];
        if ($this->exist && $path = $this->path) {
            if (!is_dir($to)) {
                mkdir($to, 0775, true);
            }
            $out[1] = [];
            foreach (g($path, null, true) as $k => $v) {
                $out[0][] = $k;
                if (is_file($v = $to . str_replace($path, "", $k))) {
                    // Return `false` if file already exists
                    $out[1][] = false;
                } else {
                    // Return `$v` on success, `null` on error
                    $out[1][] = rename($path, $v) ? $v : null;
                }
            }
        }
        $this->value = $out;
        return $this;
    }

    public function name() {
        return $this->exist ? basename($this->path) : null;
    }

    public function parent(int $i = 1) {
        return $this->exist ? dirname($this->path, $i) : null;
    }

    public function size(string $unit = null, int $r = 2) {
        if (null !== ($size = $this->_size())) {
            return File::sizer($size, $unit, $r);
        }
        return File::sizer(0, $unit, $r);
    }

    public function stream($x = null, $deep = 0): \Generator {
        return $this->exist ? g($this->path, $x, $deep) : yield from [];
    }

    public function time(string $format = null) {
        if ($this->exist) {
            $t = filectime($this->path);
            return $format ? strftime($format, $t) : $t;
        }
        return null;
    }

    public function type() {
        return null;
    }

    public function x() {
        return null;
    }

    public static function exist($path) {
        if (is_array($path)) {
            foreach ($path as $v) {
                if ($v && is_dir($v)) {
                    return realpath($v);
                }
            }
            return false;
        }
        return is_dir($path) ? realpath($path) : false;
    }

    public function let($any = true) {
        $out = [];
        if ($this->exist) {
            $path = $this->path;
            if (true === $any) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $k) {
                    $v = $k->getPathname();
                    if ($k->isDir()) {
                        $out[$v] = rmdir($v) ? 0 : null;
                    } else {
                        $out[$v] = unlink($v) ? 1 : null;
                    }
                }
                $out[$path] = rmdir($path) ? 0 : null;
            } else  {
                foreach ((array) $any as $v) {
                    $v = $path . DS . strtr($v, '/', DS);
                    if (is_file($v)) {
                        $out[$v] = unlink($v) ? 1 : null;
                    } else {
                        $out[$v] = (new static($v))->let() ? 0 : null;
                    }
                }
            }
        }
        return $out;
    }

}