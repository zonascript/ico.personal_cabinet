<?php

namespace Modules\Akara\Address\Coin;


use Modules\Akara\Address\Interfaces\AddressInterface;

abstract class AddressBase implements AddressInterface
{
    protected $address = null;

    public function __construct($address)
    {
        $this->address = $address;
    }

    public static function getAddressEntity($coin, $address)
    {
        $coin = ucfirst($coin);
        $add = "Modules\\Akara\\Address\\Coin\\$coin";
        if (class_exists($add)) {
            return new $add($address);
        }

        return null;
    }

    public function getAddressInfo()
    {

    }

    public function getTransactions()
    {

    }
}