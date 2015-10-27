<?php

/**
 * GVNdatum normalizes dates from "Geheugen van Nederland" to an ISO 8601 start and end date
 * @author René Voorburg
 * @date 2015-10-15
 * @version 2015-10-27
 * thanks to Hack-a-LOD
 *
 * See testdata.json for dates that are understood.
 * Focus has been to support "Geheugen van Nederland", improvement for more general purposes welcome.
 *
 * These formats are currently problematic:
 * - "1892, 15/16 aug."
 * - "1911, September 11-16"
 * - "1938, Pinksteren"
 */

class GVNdatum
{
    const DAY_RE = '[0-3]?[0-9]';
    const MONTH_RE = '[0-1]?[0-9]';
    const YEAR_RE = '[0-9]{3,4}';                   // year, should have at least three digits

    const PARTSEP_RE = '[\.\-|/| ]';               // separator betweens parts of a date
    const UNTILSEP_RE = '[ ]?(-|/|t.m|tot|voltooid|en)[ ]?';   // separates two dates to indicate a range

    private $originalDateStr;
    private $processedDateStr;
    private $period = array(
        'start' => array('day' => null, 'month' => null, 'year' => null),
        'end' => array('day' => null, 'month' => null, 'year' => null)
    );

    /**
     * formats the results
     * @param array $date ; use $this->period['start'] or $this->period['end']
     * @return string ; date in ISO 8601 format
     */
    private function dateString($which)
    {
        $ret = '';
        if ($which == 'start') {
            $ret .= $this->getStartYear() ;
            $ret .= $this->getStartMonth() ? '-'.$this->getStartMonth() : '';
            $ret .= $this->getStartDay() ? '-'.$this->getStartDay() : '';
        } else {
            $ret .= $this->getEndYear() ;
            $ret .= $this->getEndMonth() ? '-'.$this->getEndMonth() : '';
            $ret .= $this->getEndDay() ? '-'.$this->getEndDay() : '';
        }


        return $ret;
    }

    /**
     * takes the $this->processedDateStr and uses it to fill $this->period
     */
    private function extractPeriod()
    {
        $date_RE = '(((' . self::DAY_RE . ')' . self::PARTSEP_RE . ')?(' . self::MONTH_RE . ')' . self::PARTSEP_RE . ')?(' . self::YEAR_RE . ')';
        $untildatepart_RE = '(' . self::UNTILSEP_RE . $date_RE . ')?';

        // ugly branch for yet unprocessed dates like "van -150000 tot -100000"
        if (preg_match('#^van (-?[0-9]*) tot (-?[0-9]*)$#', $this->processedDateStr, $matches)) {
            $this->period['start']['year'] = $matches[1];
            $this->period['end']['year'] = $matches[2];

        } else {
            preg_match('#' . $date_RE . $untildatepart_RE . '#', $this->processedDateStr, $matches);

            $day = isset($matches[3]) ? (int)$matches[3] : null;
            $month = isset($matches[4]) ? (int)$matches[4] : null;
            $year = isset($matches[5]) ? (int)$matches[5] : null;


            if (isset($matches[0]) && $year != 0) {
                // we have a first match:
                if ($matches[1] && $month > 0) {
                    // have a month
                    if (($matches[2] && $day > 0)) {
                        // have a day
                        $this->period['start']['day'] = $day;
                    }
                    $this->period['start']['month'] = $month;
                }
                $this->period['start']['year'] = $year;

                $day = isset($matches[10]) ? (int)$matches[10] : null;
                $month = isset($matches[11]) ? (int)$matches[11] : null;
                $year = isset($matches[12]) ? (int)$matches[12] : null;
                if (isset($matches[6]) && $year != 0) {
                    // we have a second match:
                    if ($matches[8] && $month > 0) {
                        // have a month
                        if (($matches[9] && $day > 0)) {
                            // have a day
                            $this->period['end']['day'] = $day;
                        }
                        $this->period['end']['month'] = $month;
                    }
                    $this->period['end']['year'] = $year;
                }
            }
        }

    }


    /**
     * @param string $dateStr ;
     * analyses $dateStr to fill $this->period with start and possibly end dates
     */
    public function __construct($dateStr)
    {
        // store original
        $this->originalDateStr = $dateStr;

        // cleanups
        $dateStr = trim(strtolower($dateStr));

        // periods: expand "18XX " etc to period '1800-1899'
        if (preg_match('#^[0-9]{1,3}[xX\.\?]{1,2}$#', $dateStr)) {
            $dateStr = preg_replace('#([0-9]*)[xX\.\?]{2}#', '${1}00-${1}99', $dateStr);
            $dateStr = preg_replace('#([0-9]*)[xX\.\?]#', '${1}0-${1}9', $dateStr);
        }

        // cleanups: remove some junk '[circa]', 'ongedateerd':
        $dateStr = preg_replace('#\?#', '', $dateStr);
        $dateStr = preg_replace('#\[#', '', $dateStr);
        $dateStr = preg_replace('#\]#', '', $dateStr);
        $dateStr = preg_replace('#\(#', '', $dateStr);
        $dateStr = preg_replace('#\)#', '', $dateStr);
        $dateStr = preg_replace('#,#', ' ', $dateStr);
        $dateStr = preg_replace('#\. #', ' ', $dateStr);
        $dateStr = preg_replace('#\b[a-z]*dag[a-z]*\b#', '', $dateStr);
        $dateStr = preg_replace('#ongedateerd#', '', $dateStr);
        $dateStr = preg_replace('#circa#', '', $dateStr);

        // cleanups: remove redundant spaces:
        $dateStr = preg_replace('#[ ]+#', ' ', $dateStr);
        $dateStr = trim($dateStr);

        // reformats

        // "1916, 14 october" => reformat to "14 october 1916"
        $dateStr = preg_replace('#^([0-9]{4}),? +([0-9]{1,2}) +([a-z]*)\.?$#', '${2} ${3} ${1}', $dateStr);

        // "1947, februari" => reformat to "februari 1947"
        $dateStr = preg_replace('#^([0-9]{4}),? +([a-z]*)\.?$#', '${2} ${1}', $dateStr);

        // reformats: replace names of months:
        $month['1'] = '(\bjan[a-z]*\b|\blouwmaand\b)';
        $month['2'] = '(\bfeb[a-z]*\b|\bsprokkelmaand\b)';
        $month['3'] = '(\bmaa[a-z]*\b|\blentemaand\b)';
        $month['4'] = '(\bapr[a-z]*\b|\bgrasmaand\b)';
        $month['5'] = '(\bmei\b|\bbloeimaand\b)';
        $month['6'] = '(\bjun[a-z]*\b|\bzomermaand\b)';
        $month['7'] = '(\bjul[a-z]*\b|\bhooimaand\b)';
        $month['8'] = '(\baug[a-z]*\b|\boogstmaand\b)';
        $month['9'] = '(\bsep[a-z]*\b|\bherfstmaand\b)';
        $month['10'] = '(\bokt[a-z]*\b|\bwijnmaand\b|\bzaaimaand\b|\boct[a-z]*\b)';
        $month['11'] = '(\bnov[a-z]*\b|\bslachtmaand\b)';
        $month['12'] = '(\bdec[a-z]*\b|\bwintermaand\b)';
        if (preg_match('#[a-z]{3}#', $dateStr)) {
            foreach ($month as $key => $value) {
                $dateStr = preg_replace('#' . $value . '\.?#', $key, $dateStr);
            }
        }

        // revert ISO (sorry...)
        $dateStr = preg_replace('#^(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')-('.self::DAY_RE . ')$#',
            '${3}-${2}-${1}', $dateStr);
        $dateStr = preg_replace('#^(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')$#',
            '${2}-${1}', $dateStr);
        $dateStr = preg_replace('#^(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')-('.self::DAY_RE . ')'.self::UNTILSEP_RE.
            '(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')-('.self::DAY_RE . ')$#',
            '${3}-${2}-${1} - ${7}-${6}-${5}', $dateStr);
        $dateStr = preg_replace('#^(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')'.self::UNTILSEP_RE.
            '(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')-('.self::DAY_RE . ')$#',
            '${2}-${1} - ${6}-${5}-${4}', $dateStr);
        $dateStr = preg_replace('#^(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')-('.self::DAY_RE . ')'.self::UNTILSEP_RE.
            '(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')$#',
            '${3}-${2}-${1} - ${6}-${5}', $dateStr);
        $dateStr = preg_replace('#^(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')'.self::UNTILSEP_RE.
            '(' . self::YEAR_RE . ')-(' . self::MONTH_RE . ')$#',
            '${2}-${1} - ${5}-${4}', $dateStr);


        // reformat: "1 9 0 4 " etc to "1904"
        if (preg_match('#^([0-9][ ]?[0-9][ ]?[0-9][ ]?[0-9])[^0-9]?[^0-9]?$#', $dateStr)) { // allow some junk at end
            $dateStr = preg_replace('#[ ]#', '', $dateStr);
        }

        // reformat: '12-10-53' etc to '12-10-1953'
        $dateStr = preg_replace('#^(' . self::DAY_RE . self::PARTSEP_RE . self::MONTH_RE . self::PARTSEP_RE . ')([1-9][0-9])$#',
            '${1}19${2}', $dateStr);

        // reformat: 'negentiende eeuw' to '19e eeuw' etc:
        if (preg_match('#eeuw#', $dateStr)) {
            $dateStr = preg_replace('#\bachttiende#', '18e', $dateStr);
            $dateStr = preg_replace('#\bnegentiende#', '19e', $dateStr);
            $dateStr = preg_replace('#\btwintigste#', '20e', $dateStr);
        }

        /* periods: */

        // periods: 'winter 1950 - 1951', etc
        $dateStr = preg_replace('#^winter (' . self::YEAR_RE . ') ?' . self::PARTSEP_RE . ' ?(' . self::YEAR_RE . ')$#',
            '21-12-${1} - 20-03-${2}', $dateStr);
        // winter 1944/45
        if (preg_match('#^winter ('.self::YEAR_RE.')[/\-]([0-9]{2})$#', $dateStr, $matches)) {
            $yr = (int)$matches[1];
            $dateStr = '21-12-'.(string)$yr.' - 20-03-'.(string)($yr+1);
        }
        $dateStr = preg_replace('#^(voorjaar|lente)' . self::PARTSEP_RE . '(' . self::YEAR_RE . ')$#',
            '21-3-${2} - 20-06-${2}', $dateStr);
        $dateStr = preg_replace('#^(' . self::YEAR_RE . '),? (voorjaar|lente)$#',
            '21-3-${1} - 20-06-${1}', $dateStr);
        $dateStr = preg_replace('#^zomer' . self::PARTSEP_RE . '(' . self::YEAR_RE . ')$#', '21-6-${1} - 20-09-${1}',
            $dateStr);
        $dateStr = preg_replace('#^(' . self::YEAR_RE . '),? zomer$#',
            '21-6-${1} - 20-09-${1}', $dateStr);
        $dateStr = preg_replace('#^(herfst|najaar)' . self::PARTSEP_RE . '(' . self::YEAR_RE . ')$#',
            '21-9-${2} - 20-12-${2}', $dateStr);
        $dateStr = preg_replace('#^(' . self::YEAR_RE . '),? (herfst|najaar)$#',
            '21-9-${1} - 20-12-${1}', $dateStr);

        // historical periods
        $dateStr = preg_replace('#^romeinse tijd$#', '001 - 400', $dateStr);
        $dateStr = preg_replace('#^(middeleeuwen|middeleeuws)$#', '500 - 1499', $dateStr);
        $dateStr = preg_replace('#^eerste wereldoorlog$#', '28-7-1914 - 11-11-1918', $dateStr);
        $dateStr = preg_replace('#^tweede wereldoorlog$#', '1-9-1939 - 15-8-1945', $dateStr);

        // 16e tot|en 18e eeuw - als t/m
        if (preg_match('#^([0-9]{2})d?e (eeu?w ?)?('.self::UNTILSEP_RE.'|en) ([0-9]{2})d?e eeu?w$#', $dateStr, $matches)) {
            $centuries = (int)$matches[1] - 1;
            $centuries2 = (int)$matches[5] - 1;
            $dateStr = $centuries.'00 - '. $centuries2.'99';
        }

        /* periods that can have modifiers: */

        // periods: read modifiers:
        $kwart = 0;
        if (preg_match('#\bkwart\b#', $dateStr)) {
            if (preg_match('#(eerste|1s?t?e) kwart\b#', $dateStr)) {
                $kwart = 1;
            }
            if (preg_match('#(tweede|2d?e) kwart\b#', $dateStr)) {
                $kwart = 2;
            }
            if (preg_match('#(derde|3d?e) kwart\b#', $dateStr)) {
                $kwart = 3;
            }
            if (preg_match('#(vierde|4d?e|laatste) kwart\b#', $dateStr)) {
                $kwart = 4;
            }
        }
        $helft = 0;
        if (preg_match('#\bhelft\b#', $dateStr)) {
            if (preg_match('#(eerste|1s?t?e) helft\b#', $dateStr)) {
                $helft = 1;
            }
            if (preg_match('#(tweede|2d?e|laatste) helft\b#', $dateStr)) {
                $helft = 2;
            }
        }
        $tert = 0;
        if (preg_match('#\bbegin\b#', $dateStr)) {
            $tert = 1;
        }
        if (preg_match('#\b(mid(den)?|halverwege)\b#', $dateStr)) {
            $tert = 2;
        }
        if (preg_match('#\beinde?\b#', $dateStr)) {
            $tert = 3;
        }

        // periods with optional modifiers: "vijftiger jaren" etc
        if (preg_match('#jaren#', $dateStr)) {
            $f = '0';
            $t = '9';
            switch ($kwart) {
                case 1:
                    $f = '0';
                    $t = '3';
                    break;
                case 2:
                    $f = '2';
                    $t = '5';
                    break;
                case 3:
                    $f = '5';
                    $t = '8';
                    break;
                case 4:
                    $f = '7';
                    $t = '9';
                    break;
            }
            switch ($tert) {
                case 1:
                    $f = '0';
                    $t = '3';
                    break;
                case 2:
                    $f = '3';
                    $t = '6';
                    break;
                case 3:
                    $f = '7';
                    $t = '9';
                    break;
            }
            switch ($helft) {
                case 1:
                    $f = '0';
                    $t = '5';
                    break;
                case 2:
                    $f = '5';
                    $t = '9';
                    break;
            }

            $dateStr = preg_replace('#jaren dertig#', '193' . $f . '-193' . $t, $dateStr);
            $dateStr = preg_replace('#jaren veertig#', '194' . $f . '-194' . $t, $dateStr);

            $dateStr = preg_replace('#vijftiger jaren#', '195' . $f . '-195' . $t, $dateStr);
            $dateStr = preg_replace('#zestiger jaren#', '196' . $f . '-196' . $t, $dateStr);
            $dateStr = preg_replace('#zeventiger jaren#', '197' . $f . '-197' . $t, $dateStr);
            $dateStr = preg_replace('#tachtiger jaren#', '198' . $f . '-198' . $t, $dateStr);

            $dateStr = preg_replace("#([1-9])0['\-]?[ ]?er jaren#", '19${1}' . $f . '-19${1}' . $t, $dateStr);

            $dateStr = preg_replace("#jaren '?([1-9])0" . self::PARTSEP_RE . "'?([1-9])0#", '19${1}0-19${2}9',
                $dateStr);
            $dateStr = preg_replace("#jaren '?([1-9])0#", '19${1}' . $f . '-19${1}' . $t, $dateStr);
        }

        // periods with optional modifiers: "19e eeuw" etc.
        if (preg_match('#\b([0-9]{1,2})(d?e?|ste)?( |-)(eeu?ws?|e\.).*$#', $dateStr, $matches)) {
            $f = '00';
            $t = '99';
            switch ($kwart) {
                case 1:
                    $f = '00';
                    $t = '24';
                    break;
                case 2:
                    $f = '25';
                    $t = '49';
                    break;
                case 3:
                    $f = '50';
                    $t = '74';
                    break;
                case 4:
                    $f = '75';
                    $t = '99';
                    break;
            }
            switch ($tert) {
                case 1:
                    $f = '00';
                    $t = '19';
                    break;
                case 2:
                    $f = '40';
                    $t = '59';
                    break;
                case 3:
                    $f = '80';
                    $t = '99';
                    break;
            }
            switch ($helft) {
                case 1:
                    $f = '00';
                    $t = '49';
                    break;
                case 2:
                    $f = '50';
                    $t = '99';
                    break;
            }

            $centuries = (int)$matches[1] - 1;
            $dateStr = preg_replace('#\b([0-9]{1,2})(d?e?|ste)?( |-)(eeu?w|e\.).*$#',
                $centuries . $f . ' - ' . $centuries . $t, $dateStr);
        }

//        // 12345 is incorrect
//        if (preg_match('#[0-9]{5}#', $dateStr)) {
//            $dateStr = '';
//        }



        $this->processedDateStr = $dateStr;
        $this->extractPeriod();
    }

    public function getStartDate()
    {
        return $this->dateString('start');
    }

    public function getEndDate()
    {
        return $this->dateString('end');
    }

    public function getStartYear() {
        $ret = '';
        $year = $this->period['start']['year'] ? $this->period['start']['year'] : '';
        if ($year) {
            if ($year < 0) {
                $ret .= '-';
            }
            $ret .= str_pad(abs($year), 4, "0", STR_PAD_LEFT);
        }
        return $ret;
    }

    public function getStartMonth() {
        $ret = '';
        $month = $this->period['start']['month'] ? $this->period['start']['month'] : '';
        if ($month) {
            $ret .= str_pad($month, 2, "0", STR_PAD_LEFT);
        }
        return $ret;
    }

    public function getStartDay() {
        $ret = '';
        $day = $this->period['start']['day'] ? $this->period['start']['day'] : '';
        if ($day) {
            $ret .= str_pad($day, 2, "0", STR_PAD_LEFT);
        }
        return $ret;
    }

    public function getEndYear() {
        $ret = '';
        $year = $this->period['end']['year'] ? $this->period['end']['year'] : '';
        if ($year) {
            if ($year < 0) {
                $ret .= '-';
            }
            $ret .= str_pad(abs($year), 4, "0", STR_PAD_LEFT);
        }
        return $ret;
    }

    public function getEndMonth() {
        $ret = '';
        $month = $this->period['end']['month'] ? $this->period['end']['month'] : '';
        if ($month) {
            $ret .= str_pad($month, 2, "0", STR_PAD_LEFT);
        }
        return $ret;
    }

    public function getEndDay() {
        $ret = '';
        $day = $this->period['end']['day'] ? $this->period['end']['day'] : '';
        if ($day) {
            $ret .= str_pad($day, 2, "0", STR_PAD_LEFT);
        }
        return $ret;
    }

}