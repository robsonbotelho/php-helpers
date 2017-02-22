<?php
namespace helpers;

class Utils
{
    /**
     * Verifica se a string passada está serializada ou não.
     * @param $data - A string a ser verificada.
     * @param bool $strict
     * @return bool
     */
    public static function isSerialized($data, $strict = true)
    {
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            if (false === $semicolon && false === $brace)
                return false;
            if (false !== $semicolon && $semicolon < 3)
                return false;
            if (false !== $brace && $brace < 4)
                return false;
        }
        $token = $data[0];
        switch ($token) {
            case 's' :
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
            case 'a' :
            case 'O' :
                return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b' :
            case 'i' :
            case 'd' :
                $end = $strict ? '$' : '';
                return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }
        return false;
    }

    /**
     * Retorna a string passada no argumento $data desserializada. Caso a string não esteja serializada, retorna seu
     * valor normal.
     * @param $data - A string a ser desserializada.
     * @param bool $returnArray - Se a string passada tiver valor null, retorna um array vazio. Padrão: false.
     * @return array|mixed
     */
    public static function unserialize($data, $returnArray = false)
    {
        if (self::isSerialized($data)) {
            return unserialize($data);
        }
        return (!$returnArray) ? $data : [];
    }

    /**
     * Verifica se a string passada é uma data válida (dd/mm/yyyy ou yyyy-mm-dd)
     * @param $date
     * @return bool
     */
    public static function isDate($date)
    {
        $da = explode('-', $date);
        $db = explode('/', $date);

        if (count($da) == 3) $d = $da;
        elseif (count($db) == 3) $d = $db;
        else return false;

        if (in_array(4, [strlen($d[0]), strlen($d[2])])) return true;

        return false;
    }

    /**
     * Converte uma data no formato dd/mm/yyyy para yyyy-mm-dd e vice-versa.
     * @param string $date - String contendo a data a ser convertida.
     * @param bool $hideYear - Se true, o ano não será exibido
     * @return string A data convertida ou null, caso a data informada seja inválida.
     */
    public static function dateFix($date = null, $hideYear = false) {
        $d = explode ('-', $date);
        if (count($d) === 3 && strlen($d[0]) === 4) return $d[2].'/'.$d[1].(($hideYear) ? '' : '/'.$d[0]);
        $d = explode ('/', $date);
        if (count($d) === 3 && strlen($d[2]) === 4) return (($hideYear) ? '' : $d[2].'-').$d[1].'-'.$d[0];
        return null;
    }

    /**
     * Formata uma string DateTime para o seguinte formato: dd/mm/yyy - 00:00:00
     * @param $datetime - A string no formato datetime
     * @param string $separator - O separador a ser utilizado. Padrão: " - "
     * @param bool $dropSeconds - Se true, oculta os segundos
     * @param bool $showDay - Se truo, exibe o dia (ex.: terça-feira, 05/07/2016 às 12:00:00)
     * @return null|string
     */
    public static function dateTimeFormat($datetime, $separator = ' - ', $dropSeconds = false, $showDay = false)
    {
        $d = explode (' ', $datetime);
        if (count($d) !== 2) return null;
        return (($showDay) ? self::day(self::dayFromDate($d[0])).', ' : '' ).self::dateFix($d[0]).$separator.(($dropSeconds) ? Timer::set()->time($d[1])->dropSeconds()->get() : $d[1]);
    }

    /**
     * Retorna um dia da semana para o valor passado. O valor retornado será o dia da semana por extenso ou abreviado.
     * Exemplos:
     * [valor]         => [retorno], [retorno com $abrev = true]
     * seg             => segunda-feira , segunda
     * segunda         => segunda-feira , seg
     * segunda-feira   => segunda       , seg
     * Mon [ou Monday] => segunda-feira , seg
     *
     * @param null $day
     * @param bool $abrev
     * @return array|null|string
     */
    public static function day($day = null, $abrev = false)
    {
        $daysShort = $daysLong = [
            'seg' => 'segunda-feira',
            'ter' => 'terça-feira',
            'qua' => 'quarta-feira',
            'qui' => 'quinta-feira',
            'sex' => 'sexta-feira',
            'sab' => 'sábado',
            'dom' => 'domingo'
        ];

        if ($day == null) return $daysLong;

        $day = str_replace('á', 'a', $day);

        foreach ($daysLong as $k => $val) {
            $dl = explode('-', $val);
            $daysShort[$k] = $dl[0];
        }

        $len = strlen($day);
        $sub = substr(explode('-', $day)[0], 0, 3);

        $long = $short = null;

        $week = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $d = strtolower($day);
        if (in_array($d, $week)) {
            $key = array_search($d, $week);
            $key = array_keys($daysLong)[$key];
            $long = $daysLong[$key];
            $short = $daysShort[$key];
        }

        if ($len === 3) if (array_key_exists($day, $daysLong)) {
            $long = $daysLong[$day];
            $short = $daysShort[$day];
        }

        if ($len > 3 && $len <=6) {
            if (array_key_exists($sub, $daysLong)) {
                $long = $daysLong[$sub];
                $short = $sub;
            }
        }

        if ($len > 6) {
            if (array_key_exists($sub, $daysLong)) {
                $long = $daysShort[$sub];
                $short = $sub;
            }
        }

        return ($abrev) ? $short : $long;
    }

    /**
     * Obtém um dia da semana a partir de uma data informada.
     * @param $date
     * @return array|null|string
     */
    public static function dayFromDate($date) {
        return Utils::day(Utils::day(date('D', strtotime($date)), true), true);
    }

    /**
     * Verifica se uma determinada data pertence ao período entre duas outras datas.
     * @param $date - Data a ser verificada
     * @param $start - Data inicial do período
     * @param $end - Data final do período
     * @return bool
     */
    public static function dateInRange($date, $start, $end) {
        $d = strtotime($date);
        $start = strtotime($start);
        $end = strtotime($end);

        if ($d >= $start && $d <= $end) return true;
        return false;
    }

    public static function findDateRange($start, $end, $inclusive = true)
    {
        $start = new \DateTime($start);
        if ($inclusive) {
            $end = new \DateTime(self::findDate($end, 1, '+'));
        } else {
            $end = new \DateTime($end);
        }
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($start, $interval, $end);
        $range = [];
        foreach ($period as $date) {
            $range[] = $date->format('Y-m-d');
        }
        return $range;
    }

    /**
     * Encontra uma data antes ou após um período de dias. Ex.: ao passar '2016-10-10' como argumento `$startDate` e '5'
     * como argumento `$days`, o método retornará 5 dias após a data informada (ou seja, 2016-10-15). Ao passar o valor
     * negativo ('-') como argumento `$op`, o valor retornado será igual a 5 dias antes da data informada (ou seja,
     * 2016-10-05).
     * @param $startDate
     * @param int $days
     * @param string $op
     * @return string
     */
    public static function findDate($startDate, $days = 0, $op = '+') {
        $dt = new \DateTime($startDate);
        $dt->modify($op.' '.$days.' days');
        return $dt->format('Y-m-d');
    }

    /**,
     * Retorna os dias úteis de um mês
     * @param int $mes
     * @param null $ano
     * @param int $feriados
     * @return int
     */
    public static function diasUteis($mes, $ano = null, $feriados = 0)
    {
        if ($ano == null) $ano = date('Y');
        $uteis = 0;
        $dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);

        for($dia = 1; $dia <= $dias_no_mes; $dia++){
            $timestamp = mktime(0, 0, 0, $mes, $dia, $ano);
            $semana    = date("N", $timestamp);

            if($semana < 6) $uteis++;

        }

        return $uteis - $feriados;
    }

    /**
     * Encontra o valor mais próximo dentro de um array
     * @param $value
     * @param array $range
     * @return mixed
     */
    public static function closestValue($value, $range)
    {
        if (!is_array($range)) return null;
        sort($range);
        $closest = null;
        foreach ($range as $item) {
            if ($closest === null || abs($value - $closest) > abs($item - $value)) {
                $closest = $item;
            }
        }
        return $closest;
    }

    /**
     * Encontra a data mais próxima dentro de um array
     * @param $date
     * @param array $range
     * @return mixed
     */
    public static function closestDate($date, $range)
    {
        if (!is_array($range)) return null;
        foreach($range as $day) {
            $interval[] = abs(strtotime($date) - strtotime($day));
        }

        asort($interval);
        $closest = key($interval);

        return $range[$closest];

    }

    /**
     * Converte strings de tempo em null quando estas forem "00:00:00"
     * @param $time
     * @return null
     */
    public static function setZeroToNull($time)
    {
        if (Timer::set()->time($time)->dropDays()->get() == '00:00:00') {
            return null;
        }
    }

    /**
     * Verifica se um arquivo (ou URL) existe. Caso não exista, o valor passado em $returnFile é retornado.
     * O uso deste método não é recomendado para verificar muitos arquivos em uma mesma página, pois pode causar lentidão no carregam
     * @param $file
     * @param bool $returnFile
     * @return bool
     */
    public static function fileExists($file, $returnFile = false)
    {
        if (!is_file($file)) {
            if (@getimagesize($file)) {
                return true;
            } else {
                return $returnFile;
            }
        }
        return true;
    }

    /**
     * Verifica se um determinado número é negativo (ou simplesmente se a string começa com "-").
     * @param $value
     * @return bool
     */
    public static function isNegative($value)
    {
        if (substr($value, 0, 1) == '-') return true;
        return false;
    }

    /**
     * Executa a função unset de forma recursiva para limpar os elementos de um array.
     * @param $array
     * @param $val
     */
    public static function recursiveUnset(&$array, $val)
    {
        if(is_array($array))
        {
            foreach($array as $key=>&$arrayElement)
            {
                if(is_array($arrayElement))
                {
                    self::recursiveUnset($arrayElement, $val);
                }
                else
                {
                    if($arrayElement == $val)
                    {
                        unset($array[$key]);
                    }
                }
            }
        }
    }

    /**
     * Obtains an object class name without namespaces
     * @param $className
     * @return string
     */
    public static function removeNamespace($className)
    {
        $class = get_class($className);

        if (preg_match('@\\\\([\w]+)$@', $class, $matches)) {
            $class = $matches[1];
        }

        return $class;
    }
}