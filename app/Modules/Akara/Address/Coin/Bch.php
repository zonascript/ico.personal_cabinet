<?php

namespace Modules\Akara\Address\Coin;


use \Blocktrail\SDK\BlocktrailSDK;
use BlockCypher\Converter\BtcConverter;

class Bch extends AddressBase
{

    public function getTransactions()
    {
        $res = $txns = [];


        $client = new BlocktrailSDK("da1e71e884a6b1c44b2695403980af33377cb577", "cab31a5e16ca90dd1befb76a5c99c66b3129e727", "BCC", false /* livenet */);

        if ($data = $client->addressTransactions($this->address, 1, 200, 'desc')) {
            foreach ($data['data'] as $txn) {
                    if (isset($txn['inputs']) && is_array($txn['inputs'])) {
                        $inp = array_filter($txn['inputs'],
                            function($arr) {
                                return $arr['address'] == $this->address;
                            }
                        );
                        if ($inp) {
                            foreach ($inp as $tinp) {
                                $res[] = [
                                    'value' => $tinp['value'],
                                    'confirmations' => $txn['confirmations'],
                                    'block' => '',
                                    'txn_hash' => $txn['hash'],
                                    'date' => $txn['time'],
                                    'input' => false,
                                ];
                            }
                        }
                    }

                    if (isset($txn['outputs']) && is_array($txn['outputs'])) {
                        $out = array_filter($txn['outputs'],
                            function($arr) {
                                return $arr['address'] == $this->address;
                            }
                        );
                        if ($out) {
                            foreach ($out as $tout) {
                                $res[] = [
                                    'value' => BtcConverter::satoshisToBtc($tout['value']),
                                    'confirmations' => $txn['confirmations'],
                                    'block' => '',
                                    'txn_hash' => $txn['hash'],
                                    'date' => static::convertDate($txn['time']),
                                    'input' => true,
                                ];
                            }
                        }
                    }
            }

        }
        return $res;
    }

    public static function convertDate($date)
    {
        return \DateTime::createFromFormat(DATE_ISO8601, $date);
    }
}