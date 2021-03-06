<?php

namespace Modules\Akara\Exchange\Crypto;


use Modules\Akara\Exchange\Interfaces\CryptoExchangeInterface;

class Bitfinex extends Crypto implements CryptoExchangeInterface
{
    // base exchange api url
    private $exchangeUrl = "https://api.bitfinex.com/v1";

    public function __construct($apiKey = null, $apiSecret = null)
    {
        parent::__construct($apiKey, $apiSecret);
        parent::setBaseUrl($this->exchangeUrl . "/");
    }

    private function send($method = null, $args = array(), $secure = true)
    {
        if (empty($method)) return $this->getErrorReturn("method was not defined!");

        $urlParams = $args;
        $uri = $this->getBaseUrl() . $method . '/' . $urlParams['market'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $execResult = curl_exec($ch);

        // check if there was a curl error
        if (curl_error($ch)) return $this->getErrorReturn(curl_error($ch));

        // check if we can decode the JSON string to a assoc array
        if ($obj = json_decode($execResult, true)) {
            if (!isSet($obj["Error"])) {
                return $this->getReturn(true, '', $obj);
            } else {
                return $this->getErrorReturn($obj["Error"]);
            }
        } else {
            return $this->getErrorReturn($execResult);
        }
    }

    public function getMarketPair($market = "", $currency = "")
    {
        return strtolower($currency . $market);
    }

    public function getTicker($args = null)
    {
        $response = $this->send("pubticker", $args, false);
        if(isSet($response["result"]) && !empty($response["result"])) {
            $result             = $response["result"];
            $result["Last"]     = floatval($result["last_price"]);
            $result["Bid"]      = floatval($result["bid"]);
            $result["Ask"]      = floatval($result["ask"]);
            $response["result"] = $result;
        }
        return $response;
    }

    public function getBalance($args = null)
    {
        // TODO: Implement getBalance() method.
    }

    public function buy($args = null)
    {
        // TODO: Implement buy() method.
    }

    public function sell($args = null)
    {
        // TODO: Implement sell() method.
    }

    public function getOrders($args = null)
    {
        // TODO: Implement getOrders() method.
    }

    public function getOrder($args = null)
    {
        // TODO: Implement getOrder() method.
    }

    public function getCurrencyUrl($args = null)
    {
        // TODO: Implement getCurrencyUrl() method.
    }

    public function getMarketHistory($args = null)
    {
        // TODO: Implement getMarketHistory() method.
    }
}