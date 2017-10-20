<?php
namespace Xcart\Helpers\CrawlerDetect;

class CrawlerDetect extends \Jaybizzle\CrawlerDetect\CrawlerDetect
{
    const MODE_DEFAULT = 1;
    const MODE_BY_IP = 2;

    private $mode;

    public function __construct(array $headers = null, $userAgent = null) {
        $this->mode = self::MODE_DEFAULT;

        parent::__construct($headers, $userAgent);
    }

    public function setCrawlers($crawlers)
    {
        $this->crawlers = $crawlers;

        if (method_exists($this->crawlers, 'getMode')) {
            $this->setMode($this->crawlers->getMode());
        }

        $this->compiledRegex = $this->compileRegex($this->crawlers->getAll());
        $this->compiledExclusions = $this->compileRegex($this->exclusions->getAll());

        return $this;
    }

    public function setExclusions($exclusions)
    {
        $this->exclusions = $exclusions;

        return $this;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function isCrawler($userAgent = null)
    {
        if ($this->mode == self::MODE_BY_IP)
        {
            $userAgent = "REMOTE_ADDR: {$_SERVER['REMOTE_ADDR']}";
        }

        return parent::isCrawler($userAgent);
    }

    public function getMatches()
    {
        if (method_exists($this->crawlers, 'getCrawlerName'))
        {
            for ($i=2; $i < count($this->matches); $i++) {
                if (!empty($this->matches[$i])) {
                    break;
                }
            }

            return $this->crawlers->getCrawlerName($i-2);
        }

        return isset($this->matches[0]) ? $this->matches[0] : null;
    }
}