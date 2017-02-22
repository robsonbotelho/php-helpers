<?php
namespace helpers;

/**
 * Class Timer - Oferece diversos métodos para tratar horas, dias, segundos, unit de aula e time.
 * Para melhor compreensão, as taxonomias utilizadas são as seguintes:
 * TEMPO - Um unit de aula, geralmente equivale a 50 minutos
 * TIME - Um valor de unit padrão no formato TIME, podendo incluir dias, horas, minutos e segundos (z:H:i:s). Exemplo: 1:15:00:00
 * DAY - Um valor em float que representa os dias
 * HOUR - Um valor em float que representa as horas
 * MINUTE - Um valor em float que representa os minutos
 * SECOND - Um valor em float que representa os segundos
 *
 * @package gerafas\helper
 */
class Timer
{
    public static $value = 0;
    public static $type = 'u'; // d(ays) / h(ours) / m(inutes) / s(econds) / t(ime) / u(units)
    public static $minutesPerUnit = 1; // quantos minutos equivalem a uma unidade (unit)

    private static $instance = null;

    # MAGIC METHODS AND HELPERS
    public function __toString()
    {
        return (string) self::$value;
    }
    public static function set()
    {
        if (self::$instance === null)
            self::$instance = new self;

        return self::$instance;
    }
    public function get()
    {
        return self::$value;
    }

    # OPERATIONS
    public function bulkSum(array $values)
    {
        foreach ($values as $value) {
            $this->plus($value);
        }

        return $this;
    }
    public function plus($value)
    {
        if (self::$type == 't') {
            $result = $this->timeToSeconds(self::$value) + $this->timeToSeconds($value);
            self::$value = $result; self::$type = 's';
            $this->toTime();
            if ($this->isNegative($result)) $this->invert();
        } else {
            self::$value += $value;
        }

        return $this;
    }
    public function minus($value, $negativeToZero = false)
    {
        if (self::$type == 't') {
            if ($this->isGreater($value, true)) {
                $result = $this->timeToSeconds(self::$value) - $this->timeToSeconds($value);
            } else {
                $result = '-'.($this->timeToSeconds($value) - $this->timeToSeconds(self::$value));
            }
            self::$value = $result; self::$type = 's';
            $this->toTime();
            if ($this->isNegative($result)) $this->invert();
        } else {
            self::$value -= $value;
        }

        if ($negativeToZero && $this->isNegative(self::$value)) {
            self::$value = '00:00:00';
        }

        return $this;
    }
    public function mult($value)
    {
        if (self::$type == 't') {
            self::$value = $this->timeToSeconds(self::$value) * $this->timeToSeconds($value);
            self::$type = 's';
            $this->toTime();
        } else {
            self::$value *= $value;
        }

        return $this;
    }
    public function dividedBy($value)
    {
        if (self::$type == 't') {
            self::$value = $this->timeToSeconds(self::$value) / $this->timeToSeconds($value);
            self::$type = 's';
            $this->toTime();
        } else {
            self::$value /= $value;
        }

        return $this;
    }
    public function dropDays()
    {
        if (!$this->isNull()) {
            $t = [];
            $negative = false;
            if (self::$type == 't') {
                $negative = $this->isNegative();
                $t = explode(':', self::$value);
                if (count($t) >= 4) {
                    $t[1] += $this->daysToHours($t[0]);
                    unset ($t[0]);
                }
            }
            $value = implode(':', $t);
            $append = '';
            if (!$this->isNegative($value)) $append = '-';
            self::$value = ($negative) ? $append.$value : $value;
        }

        return $this;
    }
    public function dropSeconds()
    {
        if (!$this->isNull()) {
            $t = [];
            if (self::$type == 't') {
                $t = explode(':', self::$value);
                if (count($t) >= 3) {
                    unset ($t[2]);
                }
            }
            self::$value = implode(':', $t);
        }

        return $this;
    }
    public function dropOperator()
    {
        $op = []; for($i=0; $i<10; $i++) $op[] = (string)$i;
        if (!in_array(substr(self::$value, 0, 1), $op)) {
            self::$value = substr(self::$value, 1, strlen(self::$value));
        }

        return $this;
    }
    public function invert()
    {
        if ($this->isNegative(self::$value)) {
            self::$value = substr(self::$value, 1, strlen(self::$value));
        } else {
            self::$value = '-'.self::$value;
        }

        return $this;
    }
    public function zeroFill($length = 2)
    {
        if (!$this->isNull() && self::$type == 't') {
            $e = explode (':', self::$value);
            self::$value = $e[0];
            $e[0] = str_pad($e[0], $length, '0', STR_PAD_LEFT);
            self::$value = implode(':', $e);
        }

        return $this;
    }
    public function setSecondsPerUnit($minutesPerUnit)
    {
        self::$minutesPerUnit = $minutesPerUnit;
        return $this;
    }

    # INITIALIZERS
    public function days($days)
    {
        self::$value = $days; self::$type = 'd'; return $this;
    }
    public function hours($hours)
    {
        self::$value = $hours; self::$type = 'h'; return $this;
    }
    public function minutes($minutes)
    {
        self::$value = $minutes; self::$type = 'm'; return $this;
    }
    public function seconds($seconds)
    {
        self::$value = $seconds; self::$type = 's'; return $this;
    }
    public function time($time)
    {
        self::$value = $time; self::$type = 't'; return $this;
    }
    public function unit($unit)
    {
        self::$value = $unit; self::$type = 'u'; return $this;
    }

    # CONVERTIONS
    public function toDays()
    {
        $val = self::$value;
        switch (self::$type) {
            case ('h') : self::$value = $this->hoursToDays($val); break;
            case ('m') : self::$value = $this->minutesToDays($val); break;
            case ('s') : self::$value = $this->secondsToDays($val); break;
            case ('t') : self::$value = $this->timeToDays($val); break;
            case ('u') : self::$value = $this->unitToDays($val); break;
        }
        self::$type = 'd';
        return $this;
    }
    public function toHours()
    {
        $val = self::$value;
        switch (self::$type) {
            case ('d') : self::$value = $this->daysToHours($val); break;
            case ('m') : self::$value = $this->minutesToHours($val); break;
            case ('s') : self::$value = $this->secondsToHours($val); break;
            case ('t') : self::$value = $this->timeToHours($val); break;
            case ('u') : self::$value = $this->unitToHours($val); break;
        }
        self::$type = 'h';
        return $this;
    }
    public function toMinutes()
    {
        $val = self::$value;
        switch (self::$type) {
            case ('d') : self::$value = $this->daysToMinutes($val); break;
            case ('h') : self::$value = $this->hoursToMinutes($val); break;
            case ('s') : self::$value = $this->secondsToMinutes($val); break;
            case ('t') : self::$value = $this->timeToMinutes($val); break;
            case ('u') : self::$value = $this->unitToMinutes($val); break;
        }
        self::$type = 'm';
        return $this;
    }
    public function toSeconds()
    {
        $val = self::$value;
        switch (self::$type) {
            case ('d') : self::$value = $this->daysToSeconds($val); break;
            case ('h') : self::$value = $this->hoursToSeconds($val); break;
            case ('m') : self::$value = $this->minutesToSeconds($val); break;
            case ('t') : self::$value = $this->timeToSeconds($val); break;
            case ('u') : self::$value = $this->unitToSeconds($val); break;
        }
        self::$type = 's';
        return $this;
    }
    public function toTime()
    {
        $val = self::$value;
        switch (self::$type) {
            case ('d') : $val = $this->daysToTime($val); break;
            case ('h') : $val = $this->hoursToTime($val); break;
            case ('m') : $val = $this->minutesToTime($val); break;
            case ('s') : $val = $this->secondsToTime($val); break;
            case ('u') : $val = $this->unitToTime($val); break;
        }
        self::$value = $val;
        self::$type = 't';
        return $this;
    }
    public function toUnits($minutesPerUnit = null)
    {
        $minutesPerUnitOriginal = self::$minutesPerUnit;
        if ($minutesPerUnit != null) {
            self::$minutesPerUnit = $minutesPerUnit;
        }

        $val = self::$value;
        switch (self::$type) {
            case ('d') : self::$value = $this->daysToUnits($val); break;
            case ('h') : self::$value = $this->hoursToUnits($val); break;
            case ('m') : self::$value = $this->minutesToUnits($val); break;
            case ('s') : self::$value = $this->secondsToUnits($val); break;
            case ('t') : self::$value = $this->timeToUnits($val); break;
        }
        self::$minutesPerUnit = $minutesPerUnitOriginal;
        self::$type = 'u';
        return $this;
    }

    # COMPARISONS
    public function isNull()
    {
        return (self::$value == null) ? true : false;
    }
    public function isGreater($value, $acceptEqual = false)
    {
        if (self::$type == 't') {
            if ($acceptEqual) {
                return ($this->timeToSeconds(self::$value) >= $this->timeToSeconds($value));
            } else {
                return ($this->timeToSeconds(self::$value) > $this->timeToSeconds($value));
            }
        } else {
            return ($acceptEqual) ? (self::$value) > $value : (self::$value) > $value;
        }
    }
    public function isLower($value, $acceptEqual = false)
    {
        if (self::$type == 't') {
            if ($acceptEqual) {
                return ($this->timeToSeconds(self::$value) <= $this->timeToSeconds($value));
            } else {
                return ($this->timeToSeconds(self::$value) < $this->timeToSeconds($value));
            }
        } else {
            return ($acceptEqual) ? (self::$value) < $value : (self::$value) > $value;
        }
    }
    public function isNegative($value = null)
    {
        $value = ($value) ? $value : self::$value;
        if (substr($value, 0, 1) === '-') return true;
        return false;
    }

    # DAYS
    private function daysToHours($days)
    {
        return $days * 24;
    }
    private function daysToMinutes($days)
    {
        return $days * 1440;
    }
    private function daysToSeconds($days)
    {
        return $days * 86400;
    }
    private function daysToTime($days)
    {
        return $this->secondsToTime($this->daysToSeconds($days));
    }
    private function daysToUnits($days)
    {
        return $this->daysToMinutes($days) / self::$minutesPerUnit;
    }

    # HOURS
    private function hoursToDays($hours)
    {
        return $hours / 24;
    }
    private function hoursToMinutes($hours)
    {
        return $hours * 60;
    }
    private function hoursToSeconds($hours)
    {
        return $hours * 3600;
    }
    private function hoursToTime($hours)
    {
        return $this->secondsToTime($this->hoursToSeconds($hours));
    }
    private function hoursToUnits($hours)
    {
        return $this->hoursToMinutes($hours) / self::$minutesPerUnit;
    }

    # MINUTES
    private function minutesToDays($minutes)
    {
        return $minutes / 1440;
    }
    private function minutesToHours($minutes)
    {
        return $minutes / 60;
    }
    private function minutesToSeconds($minutes)
    {
        return $minutes * 60;
    }
    private function minutesToTime($minutes)
    {
        return $this->secondsToTime($this->minutesToSeconds($minutes));
    }
    private function minutesToUnits($minutes)
    {
        return $minutes / self::$minutesPerUnit;
    }

    # SECONDS
    private function secondsToDays($seconds)
    {
        return $seconds / 86400;
    }
    private function secondsToHours($seconds)
    {
        return $seconds / 3600;
    }
    private function secondsToMinutes($seconds)
    {
        return $seconds / 60;
    }
    private function secondsToTime($seconds)
    {
        return gmdate('z:H:i:s', abs($seconds));
    }
    private function secondsToUnits($seconds)
    {
        return $seconds / $this->minutesToSeconds(self::$minutesPerUnit);
    }

    # TIME
    private function timeToDays($time)
    {
        return $this->hoursToDays($this->timeToHours($time));
    }
    private function timeToHours($time)
    {
        return $this->minutesToHours($this->timeToMinutes($time));
    }
    private function timeToMinutes($time)
    {
        return $this->secondsToMinutes($this->timeToSeconds($time));
    }
    private function timeToSeconds($time)
    {
        $t = array_reverse(explode(':', $time));
        for($i = 0; $i < count($t); $i++) {
            switch($i) {
                case 0 : $x = 1; break;
                case 1 : $x = 60; break;
                case 2 : $x = 3600; break;
                case 3 : $x = 86400; break;
                default: $x = 1;
            }
            $t[$i] *= $x;
        }
        return array_sum($t);
    }
    private function timeToUnits($time)
    {
        return $this->hoursToUnits($this->timeToHours($time));
    }

    # TEMPO
    private function unitToDays($unit)
    {
        return $this->minutesToDays($this->unitToMinutes($unit));
    }
    private function unitToHours($unit)
    {
        return $this->minutesToHours($this->unitToMinutes($unit));
    }
    private function unitToMinutes($unit)
    {
        return $unit * self::$minutesPerUnit;
    }
    private function unitToSeconds($unit)
    {
        return $unit * $this->minutesToSeconds(self::$minutesPerUnit);
    }
    private function unitToTime($unit)
    {
        return $this->minutesToTime($this->unitToMinutes($unit));
    }
}