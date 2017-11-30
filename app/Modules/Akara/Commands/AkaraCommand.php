<?php

namespace Modules\Akara\Commands;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use Mindy\Console\ConsoleCommand;
use Mindy\Query\Expression;
use Modules\Akara\Address\Coin\AddressBase;
use Modules\Akara\Helpers\RateHelper;
use Modules\Akara\Models\Coin;
use Modules\Akara\Models\Ico;
use Modules\Akara\Models\Rates;
use Modules\Akara\Models\Transaction;
use Modules\Akara\Helpers\BonusHelper;


class AkaraCommand extends ConsoleCommand
{
    public function actionTest()
    {
        /*$network = Bitcoin::getNetwork();
        $privateKey = PrivateKeyFactory::create(true);
        $publicKey = $privateKey->getPublicKey();
        echo "Key Info\n";
        echo " - Compressed? " . (($privateKey->isCompressed() ? 'yes' : 'no')) . "\n";
        echo "Private key\n";
        echo " - WIF: " . $privateKey->toWif($network) . "\n";
        echo " - Hex: " . $privateKey->getHex() . "\n";
        echo " - Dec: " . gmp_strval($privateKey->getSecret(), 10) . "\n";
        echo "Public Key\n";
        echo " - Hex: " . $publicKey->getHex() . "\n";
        echo " - Hash: " . $publicKey->getPubKeyHash()->getHex() . "\n";
        echo " - Address: " . $publicKey->getAddress()->getAddress() . "\n";*/

        if ($icos = Ico::objects()->filter(
            [
                'end_date__gte' => new Expression('now()'),
                'start_date__lte' => new Expression('now()')
            ])->all()
        ) {
            foreach ($icos as $ico) {
                foreach ($ico->tokens as $token) {
                    for($i=0; $i < 300; $i++) {
                        $coin_address = AddressBase::getAddressEntity($token->coin->code, $token->token);
                        $a = $coin_address->generateAddress();
                        echo $token->coin->code . "\n";
                        echo $a->address . "\n";
                        echo $a->public . "\n";
                        echo $a->private . "\n";
                        echo $a->wif . "\n";
                    }

                    break;
                }
            }
        }

    }

    public function actionGetTransactions()
    {
        $created_cnt = 0;

        if ($icos = Ico::objects()->filter(
            [
                'end_date__gte' => new Expression('now()'),
                'start_date__lte' => new Expression('now()')
            ])->all()
        ) {
            foreach ($icos as $ico) {
                foreach ($ico->tokens as $token) {

                    $coin_address = AddressBase::getAddressEntity($token->coin->code, $token->token);

                    if ($txns = $coin_address->getTransactions()) {
                        foreach ($txns as $txn) {
                            if ($txn['input']) {

                                list($t_model, $created) = Transaction::objects()->getOrCreate(['token' => $token, 'hash' => $txn['txn_hash']]);

                                if ($created) {
                                    $t_model->setAttributes(
                                        [
                                            'confirmations' => $txn['confirmations'],
                                            'amount' => $txn['value'],
                                            'date' => $txn['date']->format('Y-m-d H:i:s'),
                                            'type' => Transaction::TRANSACTION_TYPE_PURCHASE,
                                            'bonus' => BonusHelper::getActiveBonus($ico),
                                            'status' => Transaction::TRANSACTION_STATUS_COMPLETE,
                                            'rate' => RateHelper::getRate($token->coin, $ico)
                                        ]
                                    );
                                    if ($t_model->save()) {
                                        $created_cnt++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($created_cnt) {
            echo "Found $created_cnt new transactions\n";
        }

    }

    public function actionGetRates()
    {

        $markets = [
            'bittrex' => self::getModule()->getRateEntity('Bittrex'),
            'bitfinex' => self::getModule()->getRateEntity('Bitfinex'),
            'poloniex' => self::getModule()->getRateEntity('Poloniex')
        ];

        if ($icos = Ico::objects()->all()) {
            foreach ($icos as $ico) {
                if ($base = $ico->coin) {
                    if ($coins = Coin::objects()->exclude(
                        [
                            'code' => $base->code
                        ])
                        ->all()
                    ) {
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