<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 27.12.2016
 * Time: 13:47
 */

namespace Xcart\Helpers\CrawlerDetect;

use Jaybizzle\CrawlerDetect\Fixtures\AbstractProvider;

class CrawlersIp extends AbstractProvider
{
    protected $data = null;
    private $raw_data
        = [
            "Infoseek" => ['198.5.210.', '204.162.96.', '204.162.97.', '204.162.98.', '205.226.201.', '205.226.203.', '205.226.204.'],
            "Lycos"    => ['206.79.171.', '207.77.90.', '208.146.26.', '209.67.228.', '209.67.229.'],
//            "Local"    => ['127.0.0.', '192.168.1.'],
        ];

    public function __construct()
    {
        $data = [];
        foreach ($this->raw_data as $k => $v) {
            $v      = str_replace('.', '\.', implode('|', $v));
            $data[] = "($v)";
        }

        $this->data = $data;
    }

    public function getCrawlerName($n)
    {
        $h = array_keys($this->raw_data);

        return $h[$n];
    }

    public function getMode()
    {
        return CrawlerDetect::MODE_BY_IP;
    }

}