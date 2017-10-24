<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 16/07/14.07.2014 12:19
 */

namespace Modules\Core\Components;

use DateTime;
use DateInterval;
use Mindy\Locale\Translate;
use Modules\Core\CoreModule;

class Humanize
{
    /**
     * Функция возвращает окончание для множественного числа слова на основании числа и массива окончаний
     * @param  $number Integer Число на основе которого нужно сформировать окончание
     * @param $endingArray Array Массив слов или окончаний для чисел (1, 4, 5),
     *         например array('яблоко', 'яблока', 'яблок')
     * @return String
     */
    public static function getNumEnding($number, $endingArray)
    {
        $number = $number % 100;
        if ($number >= 11 && $number <= 19)
            $ending = $endingArray[2];
        else
        {
            $i = $number % 10;
            switch ($i)
            {
                case 1: $ending = $endingArray[0]; break;
                case 2:
                case 3:
                case 4: $ending = $endingArray[1]; break;
                default: $ending = $endingArray[2];
            }
        }
        return $ending;
    }

    /**
     * Рубли из строки в число
     * @param $num
     * @return string
     */
    function numToStr($num) {
        $nul='ноль';
        $ten=array(
            array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
            array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
        );
        $a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
        $tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
        $hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
        $unit=array( // Units
            array('копейка' ,'копейки' ,'копеек',	 1),
            array('рубль'   ,'рубля'   ,'рублей'    ,0),
            array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
            array('миллион' ,'миллиона','миллионов' ,0),
            array('миллиард','милиарда','миллиардов',0),
        );
        //
        list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
        $out = array();
        if (intval($rub)>0) {
            foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
                if (!intval($v)) continue;
                $uk = sizeof($unit)-$uk-1; // unit key
                $gender = $unit[$uk][3];
                list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
                // mega-logic
                $out[] = $hundred[$i1]; # 1xx-9xx
                if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
                else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
                // units without rub & kop
                if ($uk>1) $out[]= self::getNumEnding($v,[$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]]);
            } //foreach
        }
        else $out[] = $nul;
        $out[] = self::getNumEnding(intval($rub), [$unit[1][0],$unit[1][1],$unit[1][2]]); // rub
        $out[] = $kop.' '.self::getNumEnding($kop, [$unit[0][0],$unit[0][1],$unit[0][2]]); // kop
        return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
    }

    public static function humanizeDate(DateTime $date, $dateFormat = 'd.m.Y')
    {
        $now = new DateTime();
        $now->setTime(0,0,0);
        $clonedDate = clone $date;
        $clonedDate->setTime(0,0,0);
        $diff = $now->diff($clonedDate);

        if ($diff->days == 0) {
            return CoreModule::t('Today', [], 'time');
        }elseif($diff->days == 1){
            return CoreModule::t('Yesterday', [], 'time');
        }

        return $date->format($dateFormat);
    }

    public static function humanizeTime(DateInterval $diff, DateTime $date, $timeFormat = 'H:i')
    {
        if ($diff->days == 0 && $diff->h == 0 && $diff->i < 31) {
            $minutes = $diff->i;

            if ($minutes != 0) {
                $ending = [
                    CoreModule::t('minutes1', [], 'time'),
                    CoreModule::t('minutes4', [], 'time'),
                    CoreModule::t('minutes5', [], 'time')
                ];

                $ending = self::getNumEnding($minutes, $ending);

                return [false, CoreModule::t('{minutes} {ending} ago', [
                    '{minutes}' => $minutes,
                    '{ending}' => $ending
                ], 'time')];
            }else{
                return [false, CoreModule::t('Just now', [], 'time')];
            }
        }

        return [true, $date->format($timeFormat)];
    }

    public static function humanizeDateTime($dateRaw, $dateFormat = 'd.m.Y', $timeFormat = 'H:i', $delimiter = ', ')
    {
        $date = new DateTime($dateRaw);
        $now = new DateTime();
        $diff = $date->diff($now);

        $humanizeDate = self::humanizeDate($date, $dateFormat);
        list($showDate, $humanizeTime) = self::humanizeTime($diff, $date, $timeFormat);

        $humanized = [];
        if ($showDate) {
            $humanized[] = $humanizeDate;
        }
        $humanized[] = $humanizeTime;

        return implode($delimiter, $humanized);
    }

    /**
     * @param $size
     * @param bool $minimal
     * @return mixed
     */
    public static function humanizeSize($size, $minimal = true)
    {
        if ($size < 1024) {
            $converted = $size;
            $message = $minimal ? '{n} B' : '{n} byte|{n} bytes';
        } elseif ($size < pow(1024, 2)) {
            $converted = round($size / 1024);
            $message = $minimal ? '{n} KB' : '{n} kilobyte|{n} kilobytes';
        } elseif ($size < pow(1024, 3)) {
            $converted = round($size / pow(1024, 2));
            $message = $minimal ? '{n} MB' : '{n} megabyte|{n} megabytes';
        } elseif ($size < pow(1024, 4)) {
            $converted = round($size / pow(1024, 3));
            $message = $minimal ? '{n} GB' : '{n} gigabyte|{n} gigabytes';
        } else {
            $converted = round($size / pow(1024, 4));
            $message = $minimal ? '{n} TB' : '{n} terabyte|{n} terabytes';
        }

        return Translate::getInstance()->t('base', $message, $converted);
    }
}
