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
 * @date 23/10/14.10.2014 17:41
 */

namespace Modules\User\Tests;

use Modules\User\Models\Group;
use Modules\User\Models\GroupPermission;
use Modules\User\Models\Key;
use Modules\User\Models\Permission;
use Modules\User\Models\Profile;
use Modules\User\Models\Session;
use Modules\User\Models\User;
use Modules\User\Models\UserPermission;

class UserTest extends UserBaseTest
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

    public function testInit()
    {
        $this->assertEquals(0, User::objects()->count());
        $this->assertEquals(0, Group::objects()->count());
        $this->assertEquals(0, Permission::objects()->count());
        $this->assertEquals(0, UserPermission::objects()->count());
        $this->assertEquals(0, GroupPermission::objects()->count());
        $this->assertEquals(0, Key::objects()->count());
        $this->assertEquals(0, Profile::objects()->count());
        $this->assertEquals(0, Session::objects()->count());
    }

    public function testCreateUserDefaultMethod()
    {
        $user = new User(['username' => 'foo']);
        $this->assertFalse($user->isValid());
        $this->assertEquals([
            'email' => ['Is not a valid email address']
        ], $user->getErrors());

        $user->email = 'admin@admin.com';
        $this->assertTrue($user->isValid());
        $this->assertEquals([], $user->getErrors());
        $this->assertTrue($user->save());

        $this->assertEquals(1, $user->pk);
        $this->assertEquals('foo', $user->username);
        $this->assertNull($user->password);
        $this->assertFalse($user->is_staff);
        $this->assertFalse($user->is_superuser);

        // Mails don't send automatically
        $this->assertEquals([], $this->app->mail->out);
    }

    public function testCreateUserViaManager()
    {
        $user = User::objects()->createUser('foo', 'bar', 'admin@admin.com');
        $this->assertEquals(1, $user->pk);
        $this->assertEquals('foo', $user->username);
        $this->assertNotNull($user->password);
        $this->assertFalse($user->is_staff);
        $this->assertFalse($user->is_superuser);

        // Mails don't send automatically
        $this->assertEquals([], $this->app->mail->out);

        $user = User::objects()->createUser('bar', 'foo', 'admin@admin.com', [
            'is_staff' => true
        ]);
        $this->assertEquals(2, $user->pk);
        $this->assertEquals('bar', $user->username);
        $this->assertNotNull($user->password);
        $this->assertTrue($user->is_staff);
        $this->assertFalse($user->is_superuser);

        // Mails don't send automatically
        $this->assertEquals([], $this->app->mail->out);
    }

    public function testCreateSuperUserViaManager()
    {
        $user = User::objects()->createSuperUser('foo', 'bar', 'admin@admin.com');
        $this->assertEquals(1, $user->pk);
        $this->assertEquals('foo', $user->username);
        $this->assertNotNull($user->password);
        $this->assertTrue($user->is_staff);
        $this->assertTrue($user->is_superuser);

        // Mails don't send automatically
        $this->assertEquals([], $this->app->mail->out);
    }

    public function testCreateGroup()
    {
        $group = new Group(['name' => 'test']);
        $this->assertTrue($group->save());
        $this->assertEquals(1, $group->pk);
        $this->assertEquals('test', $group->name);
        $this->assertFalse($group->is_locked);
        $this->assertFalse($group->is_default);
        $this->assertTrue($group->is_visible);
        $this->assertEquals(0, $group->permissions->count());
    }

    public function testCreateGroupAndLinkUser()
    {
        $group = new Group(['name' => 'test']);
        $this->assertTrue($group->save());
        $this->assertEquals(1, $group->pk);
        $this->assertEquals('test', $group->name);
        $this->assertFalse($group->is_locked);
        $this->assertFalse($group->is_default);
        $this->assertTrue($group->is_visible);
        $this->assertEquals(0, $group->permissions->count());

        $user = User::objects()->createUser('foo', 'bar', 'admin@admin.com', [
            'groups' => [$group]
        ]);
        $this->assertEquals(1, $user->pk);
        $this->assertEquals(1, $user->groups->count());
        $this->assertEquals(1, $group->users->count());
    }

    public function testCreateUserWithDefaultGroup()
    {
        $group = new Group(['name' => 'test', 'is_default' => true]);
        $this->assertTrue($group->save());
        $user = User::objects()->createUser('foo', 'bar', 'admin@admin.com');
        $this->assertEquals(1, $user->groups->count());
    }
}
