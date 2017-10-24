<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 10/12/14 13:48
 */

namespace Modules\User\Commands;

use Mindy\Console\ConsoleCommand;
use Modules\Geo\Models\City;
use Modules\Social\Models\SocialProfile;
use Modules\User\Models\User;
use Modules\Zakaz\Models\Profile;
use Modules\Zakaz\Models\UserAddress;

class BitrixCommand extends ConsoleCommand
{
    private static function convert($str)
    {
        return iconv('cp1251', 'utf-8', $str);
    }

    public function actionMigrate()
    {
        foreach (BitrixUser::objects()->using('bitrix')->batch(30) as $models) {
            foreach ($models as $model) {
                $phones = array_filter([
                    self::convert($model->PERSONAL_PHONE),
                    self::convert($model->PERSONAL_MOBILE)
                ]);
                $profile = new Profile([
                    'first_name' => self::convert($model->NAME),
                    'last_name' => self::convert($model->LAST_NAME),
                    'middle_name' => self::convert($model->SECOND_NAME),
                    'birthday' => self::convert($model->PERSONAL_BIRTHDAY),
                    'phone' => empty($phones) ? '' : implode(', ', $phones)
                ]);
                $profile->save();

                $user = new User([
                    'username' => self::convert($model->LOGIN),
                    'email' => self::convert($model->EMAIL),
                    'is_active' => $model->getIsActive(),
                    'password' => self::convert($model->PASSWORD),
                    'hash_type' => 'bitrix',
                    'profile' => $profile
                ]);
                $user->save();

                list($city, $created) = City::objects()->getOrCreate([
                    'name' => self::convert($model->PERSONAL_CITY),
                    'country_id' => 1,
                    'region_id' => 1,
                ]);

                $address = new UserAddress([
                    'city' => $city,
                    'address' => self::convert($model->PERSONAL_STREET),
                    'user' => $user,
                    'house' => '?',
                    'apartment' => '?',
                ]);
                $address->save();

                if (!empty($model->XML_ID)) {
                    $social = new SocialProfile([
                        'user' => $user,
                        'social_id' => $model->XML_ID,
                        'info' => '',
                    ]);
                    $social->save();
                }
            }
        }

        echo 'Memory in use: ' . memory_get_usage() . ' (' . memory_get_usage() / 1024 / 1024 . 'M)' . PHP_EOL;
        echo 'Peak usage: ' . memory_get_peak_usage() . ' (' . memory_get_peak_usage() / 1024 / 1024 . 'M)' . PHP_EOL;
    }
}
