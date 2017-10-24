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
 * @date 23/10/14.10.2014 18:53
 */

namespace Modules\User\Tests;

use Mindy\Tests\DatabaseTestCase;
use Modules\User\Models\Group;
use Modules\User\Models\GroupPermission;
use Modules\User\Models\Key;
use Modules\User\Models\Permission;
use Modules\User\Models\Profile;
use Modules\User\Models\Session;
use Modules\User\Models\User;
use Modules\User\Models\UserPermission;

abstract class UserBaseTest extends DatabaseTestCase
{
    protected function getModels()
    {
        return [
            new User,
            new Group,
            new Permission,
            new UserPermission,
            new GroupPermission,
            new Key,
            new Session
        ];
    }
}
