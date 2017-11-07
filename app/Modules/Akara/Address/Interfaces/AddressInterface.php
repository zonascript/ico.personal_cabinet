<?php

namespace Modules\Akara\Address\Interfaces;


interface AddressInterface
{
    // get Crypto Address Info
    public function getAddressInfo();

    // get Crypto Address Transactions
    public function getTransactions();

}