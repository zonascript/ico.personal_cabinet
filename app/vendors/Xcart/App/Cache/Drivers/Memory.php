<?php
namespace Xcart\App\Cache\Drivers;

use Xcart\App\Cache\CacheDriver;

class Memory extends CacheDriver
{
    protected $data = [];
    protected $_stack = [];

    public $gcProbability = 10;
    public $numCacheQuery = 30;

    protected function getValue($key)
    {
        $this->gc();

        if (isset($this->data[$key])) {
            return $this->data[$key]['value'];
        }

        return null;
    }

    protected function setValue($key, $data, $timeout)
    {
        $this->gc();

        array_unshift($this->_stack, $key);
        $this->data[$key] = ['value' => $data, 'timeout' => $timeout + time()];

        if (count ($this->_stack) > $this->numCacheQuery) {
            $count = count ($this->_stack) - $this->numCacheQuery;

            for ($i=1; $count >= $i; $i++)
            {
                $key = array_pop($this->_stack);
                unset($this->data[$key]);
            }
        }
    }


    public function gc($force = false, $expiredOnly = true)
    {
        if ($force || mt_rand(0, 1000000) < $this->gcProbability) {
            if (!$expiredOnly) {
                $this->data = [];
            }
            else {
                foreach ($this->data as $key => $params)
                {
                    if ($params['timeout'] < time())
                    {
                        if ($l_key = array_search($key,$this->_stack)) {
                            unset($this->_stack[$l_key]);
                        }

                        unset($this->data[$key]);
                    }
                }
            }
        }
    }

    public function cleanUp($force = false)
    {
        $this->gc(true, !$force);
    }
}