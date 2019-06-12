<?php

namespace Eckinox\Library\General;


class Cron {
    const CRON_DATE  = [ 'min' => 'i'   , 'hour' => 'G'   , 'day' => 'j'   , 'month' => 'n'   , 'week' => 'w'   ];
    const CRON_RANGE = [ 'min' => '0-59', 'hour' => '0-23', 'day' => '1-31', 'month' => '1-12', 'week' => '0-6' ];

    protected $callback = null;

    protected $tab = [
        'min'   => "*",
        'hour'  => "*",
        'day'   => "*",
        'month' => "*",
        'week'  => "*"
    ];

    protected $output = true;

    public function setTab($tab) {
        list($min, $hour, $day, $month, $week) = explode(' ', $tab);

        $this->tab = [
            'min'   => $min,
            'hour'  => $hour,
            'day'   => $day,
            'month' => $month,
            'week'  => $week,
        ];

        return $this;
    }

    public function reset() {
        $this->tab = [
            'min'   => "*",
            'hour'  => "*",
            'day'   => "*",
            'month' => "*",
            'week'  => "*"
        ];

        $this->callback = null;
    }

    public function run($callback = null) {
        $retval = false;

        if ( $this->validate_cron($this->tab) ) {
            $retval = call_user_func_array($callback ?: $this->callback, []);
        }

        $this->reset();
        return $retval;
    }

    public function validate_cron($cron) {
        $now = new \DateTime();
        is_string($cron) && ( $cron = array_combine(array_keys(static::CRON_DATE), array_slice(explode(' ', $cron, 6), 0, 5)) );

        foreach($cron as $key => $value) {
            $item_range = $this->_get_range_values($key, $value);

            if ( ! in_array((int)$now->format(static::CRON_DATE[$key]), $item_range, false) ){
                return false;
            }
        }

        return true;
    }

    public function callback($set = null) {
        return $set !== null ? $this->callback = $set : $this->callback;
    }

    public function min($value) {
        $this->tab['min'] = $value;
        return $this;
    }

    public function hour($value) {
        $this->tab['hour'] = $value;
        return $this;
    }

    public function day($value) {
        $this->tab['day'] = $value;
        return $this;
    }

    public function week($value) {
        $this->tab['week'] = $value;
        return $this;
    }

    public function month($value) {
        $this->tab['month'] = $value;
        return $this;
    }
    /**
     * Running given function every x minute
     * @param mixed $value
     * @param mixed $range
     * @return $this
     */
    public function every_min($value = "*", $range = "*") {
        $this->tab['min'] = "$range/$value";
        return $this;
    }

    public function every_even_min() {
        return $this->every_min(2);
    }

    public function every_odd_min() {
        return $this->every_min("2", "1-59");
    }

    /**
     * Running given function every x hour
     * @param mixed $value
     * @param mixed $range
     * @return $this
     */
    public function every_hour($value = "*", $range = "*") {
        $this->tab['hour'] = "$range/$value";
        return $this;
    }

    public function every_even_hour() {
        return $this->every_hour(2);
    }

    public function every_odd_hour() {
        return $this->every_hour(2, "1-23");
    }

    /**
     * Running given function every x day
     * @param mixed $value
     * @param mixed $range
     * @return $this
     */
    public function every_day($value = "*", $range = "*") {
        $this->tab['day'] = "$range/$value";
        return $this;
    }

    public function every_even_day() {
        return $this->every_day(2);
    }

    public function every_odd_day() {
        return $this->every_day(2, "1-31");
    }

    /**
     * Running given function every x month
     * @param mixed $value
     * @param mixed $range
     * @return $this
     */
    public function every_month($value = "*", $range = "*") {
        $this->tab['day'] = "$range/$value";
        return $this;
    }

    public function every_even_month() {
        return $this->every_month(2);
    }

    public function every_odd_month() {
        return $this->every_month(2, "1-11");
    }

    /* Filter by days of the week */
    public function sunday() {
        $this->tab['week'][] = 0;
        return $this;
    }

    public function monday() {
        $this->tab['week'][] = 1;
        return $this;
    }

    public function tuesday() {
        $this->tab['week'][] = 2;
        return $this;
    }

    public function wednesday() {
        $this->tab['week'][] = 3;
        return $this;
    }

    public function thursday() {
        $this->tab['week'][] = 4;
        return $this;
    }

    public function friday() {
        $this->tab['week'][] = 5;
        return $this;
    }

    public function saturday() {
        $this->tab['week'][] = 6;
        return $this;
    }

    public function weekend() {
        $this->tab['week'] = array_merge($this->tab['week'], [0, 6]);
        return $this;
    }

    public function weekday() {
        $this->tab['week'] = array_merge($this->tab['week'], [1, 2, 3, 4, 5]);
        return $this;
    }

    /**
     * Evaluate given range from CRON value
     *
     * @param type $key
     * @param type $range
     * @return type
     */
    protected function _get_range_values($key, $range) {
        # Allows every values
        if ( $range === "*" ) {
            list($min, $max) = explode('-', static::CRON_RANGE[$key]);
            return range($min, $max);
        }
        # Already an array
        elseif ( is_array($range) ) {
            return array_unique($range);
        }
        # Range of 1-59/2
        elseif( strpos($range , '\\') !== false) {
            list($inner_range , $step) = explode('\\' , $range);
            list($from, $to) = explode('-' , $inner_range === '*' ? static::CRON_RANGE[$key] : $inner_range);

            return range($from, $to, $step ?: 1);
        }
        # Normal patterns
        else {
            $retval = [];
            foreach(explode(',' , $range) as $item) {
                if( strpos($item, '-') !== false ) {
                    list($min , $max) = explode('-' , $item);
                    $retval[] = range($min, $max);
                }
                else {
                    $retval[] = $item;
                }
            }

            return $retval;
        }
    }
}
