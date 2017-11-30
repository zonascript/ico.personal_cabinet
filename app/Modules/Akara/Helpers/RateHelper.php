<?php

namespace Modules\Akara\Helpers;


use Modules\Akara\Models\Coin;
use Modules\Akara\Models\Ico;
use Modules\Akara\Models\Rates;

class RateHelper
{
    /**
     * @param Coin $coin
     * @param Ico $ico
     * @return Rates|null
     */
    public static function getRate(Coin $coin, Ico $ico)
    {
        /** @var Rates $r */
        $r = Rates::objects()
            ->filter(
                [
                    'ico' => $ico,
                    'coin' => $coin
                ])
            ->order(['-date_time'])
            ->limit(1)
            ->get();

        return $r;
    }
}