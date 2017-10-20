<?php
namespace Modules\Order;

use DateTime;
use Xcart\App\Main\Xcart;
use Xcart\App\Module\Module;

class OrderModule extends Module
{
    public static function onApplicationRun()
    {
        $template = Xcart::app()->template->getRenderer();

        $template->addModifier('formatprice', function($price, $thousand_delim = NULL, $decimal_delim = NULL, $precision = NULL)
        {
            return func_format_number($price, $thousand_delim, $decimal_delim, $precision);
        });

        $template->addModifier('interval_string', function($timeshtamp)
        {
            $order_age_str = '';

            if ($timeshtamp) {
                $date1 = new DateTime("now");
                $date2 = new DateTime("@{$timeshtamp}");
                $interval = $date2->diff($date1);

                $years = $interval->format("%y");
                $months = $interval->format("%m");
                $days = $interval->format("%d");
                $hours = $interval->format("%h");
                $mins = $interval->format("%i");

                $order_age_str = "";

                if ($years != 0){
                    $order_age_str .= $years." years, ";
                }

                if ($months != 0){
                    $order_age_str .= $months." months, ";
                }

                if ($days != 0){
                    $order_age_str .= $days." days, ";
                }

                $order_age_str .= sprintf('%1$02d', $hours).":". sprintf('%1$02d', $mins). " hours";
            }

            return $order_age_str;
        });

        $template->addModifier('hide_zero', function($price)
        {
            return floatval($price) ? $price : '';
        });

    }
}