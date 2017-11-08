<?php

namespace Modules\Akara\Commands;


use BlockCypher\Auth\SimpleTokenCredential;
use BlockCypher\Client\AddressClient;
use BlockCypher\Rest\ApiContext;
use Mindy\Console\ConsoleCommand;
use Modules\Akara\Address\Coin\AddressBase;
use Modules\Akara\Exchange\Crypto\Bittrex;
use Modules\Akara\Models\Coin;
use Modules\Akara\Models\Ico;
use Modules\Akara\Models\Rates;
use ResultPrinter;


class AkaraCommand extends ConsoleCommand
{
    public function actionGetRates()
    {

        $address = '1HBB6wHbhHBmsNmoBzzKdq77LKQUcUhGp3'; //BTC

        /*$address = '0x313e6f2d80601249213859d308a588e7757c8b59'; //ETH
        $address = 'LiBhd5jfJFrRUnLJFkS1r7PivWSKZMg7ZB'; //LTC

        $address = '1A4B5tPJeQr4WapSFLhJa4oHZyHptK4o8w'; //BCH*/

        $coin_address = AddressBase::getAddressEntity('BTC', $address);

        $txns = $coin_address->getTransactions($address);

        print_r($txns);


        exit;

        $markets = [
            'bittrex' => self::getModule()->getRateEntity('Bittrex'),
            'bitfinex' => self::getModule()->getRateEntity('Bitfinex'),
            'poloniex' => self::getModule()->getRateEntity('Poloniex')
        ];

        if ($icos = Ico::objects()->all()) {
            foreach ($icos as $ico) {
                if ($base = $ico->coin) {
                    if ($coins = Coin::objects()
                        ->exclude(
                            [
                                'code' => $base->code
                            ])->all()) {
                        foreach ($coins as $coin) {
                            $last = [];
                            foreach ($markets as $m_key => $market) {
                                $pair = $market->getTicker(["market" => $market->getMarketPair($base->code, $coin->code)]);
                                if (isset($pair['result']['Last']) && floatval($pair['result']['Last']) > 0) {
                                    $last[$m_key] = floatval($pair['result']['Last']);
                                }
                            }

                            if ($last) {

                                $average_rate = array_sum($last) / count($last);

                                $rates = new Rates([
                                    'coin' => $coin,
                                    'ico' => $ico,
                                    'bittrex' => round($last['bittrex'], 9),
                                    'bitfinex' => round($last['bitfinex'], 9),
                                    'poloniex' => round($last['poloniex'], 9),
                                    'average' => round($average_rate, 9),
                                    'date_time' => date('Y-m-d H:i:00', time())
                                ]);
                                $rates->save();
                            }
                        }
                    }
                }
            }
        }
    }
}