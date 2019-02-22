<?php

class Date extends Genome {

    protected static $pattern = '%Y%-%M%-%D% %~h%:%m%:%s%';
    protected static $zone = null;

    public $lot = [];
    public $o = [];
    public $parent = null;
    public $source = null;

    protected function extract() {
        if (!$this->lot) {
            global $language;
            $i = explode('.', $this->format('Y.m.d.H.i.s.A.w.h.e'));
            $this->lot = [
                '%~M%' => $language->months_long[(int) $i[1] - 1],
                '%~D%' => $language->days_long[(int) $i[7]],
                '%~h%' => $i[3],
                '%Y%' => $i[0],
                '%M%' => $i[1],
                '%D%' => $i[2],
                '%m%' => $i[4],
                '%s%' => $i[5],
                '%N%' => $i[6],
                '%W%' => str_pad($i[7] + 1, 2, '0', STR_PAD_LEFT),
                '%h%' => $i[8],
                '%Z%' => $i[9]
            ];
        }
        return $this;
    }

    public function ISO8601() {
        return $this->format('c');
    }

    public function __call(string $kin, array $lot = []) {
        $this->extract();
        $k = '%' . $kin . '%';
        if (array_key_exists($k, $this->lot)) {
            return $this->lot[$k];
        } else if ($v = self::_($kin)) {
            if (is_string($v = $v[0]) && substr_count($v, '%') >= 2) {
                return $this->pattern($v);
            }
        }
        return parent::__call($kin, $lot);
    }

    public function __construct(string $date = null) {
        if (!isset($date)) {
            $this->source = date(DATE_WISE, $_SERVER['REQUEST_TIME'] ?? time());
        } else if (is_numeric($date)) {
            $this->source = date(DATE_WISE, $date);
        } else if (strlen($date) >= 19 && substr_count($date, '-') === 5) {
            $this->source = \DateTime::createFromFormat('Y-m-d-H-i-s', $date)->format(DATE_WISE);
        } else {
            $this->source = date(DATE_WISE, strtotime($date));
        }
    }

    public function __invoke(string $pattern = null) {
        return $this->pattern($pattern ?? self::$pattern);
    }

    public function __toString() {
        return $this->source . "";
    }

    public function day($type = null) {
        $this->extract();
        if (is_string($type)) {
            return $this->lot['%~D%'];
        }
        return $this->lot['%' . ($type === 7 ? 'W' : 'D') . '%'];
    }

    public function format(string $format = DATE_WISE) {
        return date($format, strtotime($this->source)); // Generic PHP date formatter
    }

    public function hour($type = null) {
        return $this->extract()->lot['%' . ($type === 12 ? 'h' : '~h') . '%'];
    }

    public function minute() {
        return $this->format('i');
    }

    public function month($type = null) {
        return $this->extract()->lot['%' . (is_string($type) ? '~M' : 'M') . '%'];
    }

    public function pattern(string $pattern = null) {
        $this->extract();
        $pattern = $pattern ?? self::$pattern;
        if (isset($this->lot[$pattern])) {
            return $this->lot[$pattern];
        }
        return strtr(strtr(strtr($pattern, '\%', X), $this->lot), X, '%');
    }

    public function second() {
        return $this->format('s');
    }

    public function slug($separator = '-') {
        return strtr($this->source, '- :', str_repeat($separator, 3));
    }

    public function to(string $zone = 'UTC') {
        $date = new \DateTime($this->ISO8601());
        $date->setTimeZone(new \DateTimeZone($zone));
        if (!isset($this->o[$zone])) {
            $this->o[$zone] = new static($date->format(DATE_WISE));
            $this->o[$zone]->parent = $this;
        }
        return $this->o[$zone];
    }

    public function year() {
        return $this->format('Y');
    }

    public static function zone(string $zone = null) {
        if (!isset($zone)) {
            return self::$zone ?: date_default_timezone_get();
        }
        return date_default_timezone_set(self::$zone = $zone);
    }

}