<?php

namespace Modules\Akara\Exchange\Crypto;


use Modules\Akara\Exchange\Interfaces\CryptoExchangeInterface;

class Bitfinex extends Crypto implements CryptoExchangeInterface
{
    // base exchange api url
    private $exchangeUrl  = "https://api.bitfinex.com/v1";

    public function __construct($apiKey = null , $apiSecret = null)
    {
        parent::__construct($apiKey, $apiSecret);

        parent::setVersion($this->_version_major , $this->_version_minor);
        parent::setBaseUrl($this->exchangeUrl . "v" . $this->apiVersion . "/");
    }

    private function send($method = null , $args = array() , $secure = true) {
        if(empty($method)) return $this->getErrorReturn("method was not defined!");

        $urlParams  = $args;
        $uri        = $this->getBaseUrl() . $method;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $uri );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $execResult = curl_exec($ch);

        // check if there was a curl error
        if(curl_error($ch)) return $this->getErrorReturn(curl_error($ch));

        // check if we can decode the JSON string to a assoc array
        if($obj = json_decode($execResult , true)) {
            if($obj["Success"] == true) {
                if(!isSet($obj["Error"])) {
                    return $this->getReturn($obj["Success"],$obj["Message"],$obj["Data"]);
                } else {
                    return $this->getErrorReturn($obj["Error"]);
                }
            } else {
                return $this->getErrorReturn($obj["Error"]);
            }
        } else {
            return $this->getErrorReturn($execResult);
        }
    }

    public function getMarketPair($market = "" , $currency = "") {
        return strtoupper($currency . "/" . $market);
    }

    public function getTicker($args = null)
    {
        $resultOBJ = $this->send("getmarketsummary" , $args , false);
    }
}