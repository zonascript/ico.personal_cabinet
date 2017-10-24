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
 * @date 20/11/14.11.2014 17:11
 */

namespace Modules\User\Components;

/**
 * Class AuthTrait
 * @package Modules\User\Components
 */
trait AuthTrait
{
    /**
     * @var bool
     */
    private $_isGuest = true;

    /**
     * @return bool
     */
    public function getIsGuest()
    {
        return $this->_isGuest;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsGuest($value)
    {
        $this->_isGuest = $value;
        return $this;
    }
}