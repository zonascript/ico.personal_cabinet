<?php

namespace Modules\Akara\Helpers;


use Mindy\Query\Expression;
use Modules\Akara\Models\Bonus;
use Modules\Akara\Models\Ico;

class BonusHelper
{
    /**
     * @param Ico $ico
     * @return null|Bonus
     */
    public static function getActiveBonus(Ico $ico)
    {
        if ($ico) {
            if ($bonus = $ico->bonuses
                ->filter(['end_date__gte' => new Expression('now()')])
                ->limit(1)
                ->order(['end_date'])
                ->get()) {
                return $bonus;
            }
        }
        return null;
    }
}