<?php

namespace Modules\Akara\Address\Coin;

use BlockCypher\Auth\SimpleTokenCredential;
use BlockCypher\Client\AddressClient;
use BlockCypher\Rest\ApiContext;

use Modules\Akara\Address\Interfaces\AddressInterface;

class Eth extends AddressBase
{
    private $token = 'ddccefcc72a04e429345096ac01fc2a5';
    private $apiContext = null;

    public function __construct($address)
    {
        parent::__construct($address);

        $this->apiContext = ApiContext::create(
            'main', 'eth', 'v1',
            new SimpleTokenCredential($this->token),
            //array('log.LogEnabled' => true, 'log.FileName' => 'BlockCypher.log', 'log.LogLevel' => 'DEBUG')
            []
        );
    }

    public function getAddressInfo()
    {
        $addressClient = new AddressClient($this->apiContext);
        return $addressClient->get($this->address);
    }

    public function getTransactions()
    {
        $res = [];

        if ($addr = $this->getAddressInfo()) {
            if ($txns = $addr->getAllTxrefs()) {
                foreach ($txns as $txn) {
                    $res[] = [
                        'value' => self::WeiToEth($txn->getValue()),
                        'confirmations' => $txn->getConfirmations(),
                        'block' => $txn->getBlockHeight(),
                        'txn_hash' => $txn->getTxHash(),
                        'date' => static::convertDate($txn->getConfirmed()),
                        'input' => $txn->getTxOutputN() >= 0,
                    ];
                }
            }
        }

        return $res;
    }

    public function generateAddress()
    {
        $addressClient = new AddressClient($this->apiContext);
        return $addressClient->generateAddress();
    }

    public static function WeiToEth($wei)
    {

        return $wei / pow(10, 18);
    }

    public static function convertDate($date)
    {
        return \DateTime::createFromFormat(DATE_ISO8601, $date);
    }
}