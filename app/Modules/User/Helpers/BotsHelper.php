<?php

namespace Modules\User\Helpers;

use Xcart\Helpers\CrawlerDetect\CrawlerDetect;
use Xcart\Helpers\CrawlerDetect\Crawlers;
use Xcart\Helpers\CrawlerDetect\CrawlersIp;

class BotsHelper
{
    private static $isRobot = null;

    public static function IsBot()
    {
        if (is_null(self::$isRobot)) {
            $cr = new CrawlerDetect;
            if ($cr->isCrawler()
                || $cr->setCrawlers(new Crawlers())->isCrawler()
                || $cr->setCrawlers(new CrawlersIp())->isCrawler()
            ) {
                self::$isRobot = true;
            }
            else {
                self::$isRobot = false;
            }
        }

        return self::$isRobot;
    }
}