<?php
/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 23/10/14.10.2014 18:54
 */

namespace Modules\User\Tests;

use Modules\User\Models\User;

class AuthTest extends UserBaseTest
{
    public function tearDown()
    {
        parent::tearDown();
        $this->app->auth->logout();
    }

    public function testLoginAndLogout()
    {
        /** @var \Modules\User\Components\Auth $auth */
        $auth = $this->app->auth;
        $username = 'foo';
        $password = 'bar';
        $user = User::objects()->createUser($username, $password, 'admin@admin.com');

        $this->assertTrue($auth->getIsGuest());
        $this->assertTrue($auth->login($user));
        $this->assertFalse($auth->getIsGuest());

        $auth->logout();
        $this->assertTrue($auth->getIsGuest());
    }
}
