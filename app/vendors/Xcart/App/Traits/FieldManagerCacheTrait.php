<?php
namespace Xcart\App\Traits;

use Xcart\App\Orm\Manager;

trait FieldManagerCacheTrait
{
    private $managerCacheEnable = true;
    private $_dataCachedManager = [];

    public function __get($name)
    {
        if ($this->managerCacheEnable && !empty($this->_dataCachedManager[$name])) {
            return $this->_dataCachedManager[$name];
        }

        $result = parent::__get($name);

        if ($result instanceof Manager) {
            $this->_dataCachedManager[$name] = $result;
        }

        return $result;
    }

    public function enableManagerCache($enable = true)
    {
        $this->managerCacheEnable = $enable;
        return $this;
    }
}