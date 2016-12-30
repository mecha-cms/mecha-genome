<?php

class Union extends Genome {

    protected $union = [
        // 0 => [
        //     0 => ['\<', '\>', '\/'],
        //     1 => ['\=', '\"', '\"', '\s+'],
        //     2 => ['\<\!\-\-', '\-\-\>']
        // ],
        1 => [
            0 => ['<', '>', '/', '[\w:.-]+'],
            1 => ['=', '"', '"', ' ', '[\w:.-]+'],
            2 => ['<!--', '-->']
        ]
    ];

    protected $data = [
        'class' => null,
        'id' => null,
        'src' => null,
        'alt' => null,
        'width' => null,
        'height' => null,
        'property' => null,
        'name' => null, // [1]
        'content' => null,
        'href' => null,
        'rel' => null,
        'target' => null,
        'type' => null, // [2]
        'action' => null,
        'method' => null,
        'enctype' => null,
        'value' => null, // [3]
        'placeholder' => null, // [4]
        'label' => null,
        'selected' => null,
        'checked' => null,
        'disabled' => null,
        'readonly' => null,
        'style' => null
    ];

    public function __construct($union = []) {
        Hook::set('union.union', function($data) {
            return $data;
        });
        $this->union = Hook::NS(strtolower(static::class) . Anemon::NS . 'union', [[array_replace_recursive($this->union, $union)]]);
    }

    protected $unit = [];
    protected $dent = [];

    // Indent ...
    public static function dent($i) {
        return is_numeric($i) ? str_repeat(DENT, (int) $i) : $i;
    }

    // Encode all union's special character(s)
    public static function x($v) {
        if (!is_string($v)) return $v;
        return To::html_encode($v);
    }

    // Build union attribute(s) ...
    public function bond($a, $unit = "") {
        if (!is_array($a)) {
            $data = trim((string) $a);
            return strlen($data) ? ' ' . $data : ""; // no hook(s) applied ...
        }
        $output = "";
        $c = strtolower(static::class);
        $u = $this->union[1][1];
        $unit = $unit ? Anemon::NS . $unit : "";
        $array = Hook::NS($c . Anemon::NS . 'bond' . $unit, [array_replace($this->data, $a), substr($unit, 1)]);
        foreach ($a as $k => $v) {
            if (!isset($v)) continue;
            if (__is_anemon__($v)) {
                // class value as array
                if ($k === 'classes') {
                    $k = 'class';
                    $v = implode(' ', array_unique($v));
                // HTML5 `data-*` attribute
                } else if ($k === 'data') {
                    foreach ($v as $kk => $vv) {
                        if (!isset($vv)) continue;
                        $a['data-' . $kk] = __is_anemon__($vv) ? json_encode($vv) : $vv;
                    }
                    unset($a['data']);
                // Inline CSS via `css` attribute
                } else if ($k === 'css') {
                    $css = "";
                    foreach ($v as $kk => $vv) {
                        if (!isset($vv)) continue;
                        $css .= ' ' . $kk . ': ' . $vv . ';';
                    }
                    $k = 'style';
                    $v = substr($css, 1);
                } else {
                    $v = __is_anemon__($v) ? json_encode($v) : $v;
                }
            }
            $output .= $u[3] . ($v !== true ? $k . $u[0] . $u[1] . self::x($v) . $u[2] : $k);
        }
        return $output;
    }

    // Base union constructor
    public function unite($unit = 'html', $content = "", $data = [], $dent = 0) {
        if (is_array($content)) {
            $content = N . call_user_func_array([$this, __METHOD__], array_merge($content, $dent + 1)) . N;
        }
        $dent = self::dent($dent);
        $c = strtolower(static::class);
        $u = $this->union[1][0];
        $s  = $dent . $u[0] . $unit . $this->bond($data, $unit);
        $s .= $content === false ? $u[1] : $u[1] . ($content ? $content : "") . $u[0] . $u[2] . $unit . $u[1];
        return Hook::NS($c . Anemon::NS . 'unit' . Anemon::NS . $unit, [$s, [$unit, $content, $data]]);
    }

    // Inverse version of `Union::unite()`
    public function apart($input, $eval = true) {
        $u = $this->union[1][0];
        $d = $this->union[1][1];
        $x_u = isset($this->union[0][0]) ? $this->union[0][0] : [];
        $x_d = isset($this->union[0][1]) ? $this->union[0][1] : [];
        $u0 = isset($x_u[0]) ? $x_u[0] : x($u[0]);
        $u1 = isset($x_u[1]) ? $x_u[1] : x($u[1]);
        $u2 = isset($x_u[2]) ? $x_u[2] : x($u[2]);
        $d0 = isset($x_d[0]) ? $x_d[0] : x($d[0]);
        $d1 = isset($x_d[1]) ? $x_d[1] : x($d[1]);
        $d2 = isset($x_d[2]) ? $x_d[2] : x($d[2]);
        $d3 = isset($x_d[3]) ? $x_d[3] : x($d[3]);
        $output = [
            0 => null, // `$.nodeName`
            1 => null, // `$.innerHTML`
            2 => []    // `$.attributes`
        ];
        if (!preg_match('/^\s*' . $u0 . '(' . $u[3] . ')(?:' . $d3 . '*' . $u2 . '?' . $u1 . '|(' . $d3 . '+.*?)' . $d3 . '*' . $u2 . '?' . $u1 . ')(([\s\S]*?)' . $u0 . $u2 . '\1' . $u1 . ')?\s*$/s', $input, $m)) return false;
        $output[0] = $m[1];
        $output[1] = isset($m[4]) ? $m[4] : false;
        if (!empty($m[2]) && preg_match_all('/' . $d3 . '+(' . $d[4] . ')(?:' . $d0 . $d1 . '([\s\S]*?)' . $d2 . ')?/s', $m[2], $mm)) {
            foreach ($mm[1] as $k => $v) {
                $s = To::html_decode($mm[2][$k]);
                $s = $eval ? e($s) : $s;
                if ($s === "" && strpos($mm[0][$k], $d[0] . $d[1] . $d[2]) === false) {
                    $s = $v;
                }
                $output[2][$v] = $s;
            }
        }
        return $output;
    }

    // Union comment
    public function __($content = "", $dent = 0, $block = N) {
        $dent = self::dent($dent);
        $begin = $end = $block;
        if (strpos($block, N) !== false) {
            $end = $block . $dent;
        }
        $c = strtolower(static::class);
        $u = $this->union[1][2];
        return Hook::NS($c . Anemon::NS . 'unit' . Anemon::NS . '__', [$dent . $u[0] . $begin . $content . $end . $u[1], [null, $content, []]]);
    }

    // Base union tag open
    public function begin($unit = 'html', $data = [], $dent = 0) {
        $dent = self::dent($dent);
        $this->unit[] = $unit;
        $this->dent[] = $dent;
        $u = $this->union[1][0];
        $c = strtolower(static::class);
        return Hook::NS($c . Anemon::NS . $unit . Anemon::NS . 'begin', [$dent . $u[0] . $unit . $this->bond($data, $unit) . $u[1], [$unit, null, $data]]);
    }

    // Base union tag close
    public function end($unit = null, $dent = null) {
        if ($unit === true) {
            // close all
            $s = "";
            foreach ($this->unit as $u) {
                $s .= $this->end() . ($dent ?: N);
            }
            return $s;
        }
        $unit = isset($unit) ? $unit : array_pop($this->unit);
        $dent = isset($dent) ? self::dent($dent) : array_pop($this->dent);
        $c = strtolower(static::class);
        $u = $this->union[1][0];
        return Hook::NS($c . Anemon::NS . $unit . Anemon::NS . 'end', [$unit ? $dent . $u[0] . $u[2] . $unit . $u[1] : "", [$unit, null, []]]);
    }

    // ...
    public function __call($kin, $lot) {
        if (!self::kin($kin)) {
            array_unshift($lot, $kin);
            return call_user_func_array([$this, 'unite'], $lot);
        }
        return parent::__callStatic($kin, $lot);
    }

}