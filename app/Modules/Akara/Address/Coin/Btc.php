<?php

namespace Modules\Akara\Address\Coin;


use BlockCypher\Auth\SimpleTokenCredential;
use BlockCypher\Client\AddressClient;
use BlockCypher\Rest\ApiContext;
use Modules\Akara\Address\Interfaces\AddressInterface;

class Btc  extends AddressBase
{
    private $token = 'ddccefcc72a04e429345096ac01fc2a5';

    public function getAddressInfo()
    {
        $apiContext = ApiContext::create(
            'main', 'btc', 'v1',
            new SimpleTokenCredential($this->token),
            //array('log.LogEnabled' => true, 'log.FileName' => 'BlockCypher.log', 'log.LogLevel' => 'DEBUG')
            []
        );

        $addressClient = new AddressClient($apiContext);
        return $addressClient->get($this->address);
    }

    public function getTransactions()
    {
        $res = [];

        if ($addr = $this->getAddressInfo()) {
            if ($txns = $addr->getAllTxrefs()) {
                foreach ($txns as $txn) {
                    $res[] = [
                        'value' => $txn->getValue(),
                        'original_value' => $txn->getValue(),
                        'confirmations' => $txn->getConfirmations(),
                        'block' => $txn->getBlockHeight(),
                        'txn_hash' => $txn->getTxHash(),
                        'date' => $txn->getConfirmed(),
                        'input' => ($txn->getTxInputN() === 0) ? true : false,
                    ];
                }
            }
        }
        return $res;

        return null;
    }
}