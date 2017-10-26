<?php

namespace Modules\Akara\Commands;


use Mindy\Console\ConsoleCommand;
use Modules\Akara\Exchange\Crypto\Bittrex;

class AkaraCommand extends ConsoleCommand
{
    public function actionGetRates()
    {
        /** @var Bittrex $market */
        if ($market = self::getModule()->getRateEntity('Bittrex')) {
            $pair1 = $market->getTicker(["market" => $market->getMarketPair("BTC","ETH")]);

            $pair2 = $market->getTicker(["market" => $market->getMarketPair("BTC","LTC")]);

            $pair3 = $market->getTicker(["market" => $market->getMarketPair("BTC","BCC")]);

            dd($pair1,$pair2,$pair3);
        }
    }
}