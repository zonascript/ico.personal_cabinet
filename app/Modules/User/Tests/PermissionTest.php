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
 * @date 23/10/14.10.2014 19:04
 */

namespace Modules\User\Tests;

use Modules\User\Models\Permission;
use Modules\User\Models\User;

class PermissionTest extends UserBaseTest
{
    /**
     * @var \Modules\User\Components\Permissions
     */
    public $p;

    public function setUp()
    {
        parent::setUp();
        $this->p = $this->app->permissions;
    }

    public function testBizRule()
    {
        $perm = new Permission([
            'code' => 'test',
            'name' => 'Test codename',
            'is_default' => true,
            'bizrule' => '$foo=="bar"'
        ]);
        $this->assertTrue($perm->save());

        $this->p->fetchData();

        $this->assertTrue($this->p->canBizRule('test', ['foo' => 'bar']));
        $this->assertFalse($this->p->canBizRule('test', ['foo' => 'default']));
    }

    public function testPermissionSimple()
    {
        $perm = new Permission([
            'code' => 'test',
            'name' => 'Test codename'
        ]);
        $this->assertTrue($perm->isValid());
        $this->assertTrue($perm->save());
        $this->assertEquals(1, $perm->pk);

        $user = User::objects()->createUser('foo', 'bar', 'admin@admin.com', [
            'permissions' => [$perm]
        ]);
        $this->assertEquals(1, $user->permissions->count());

        $this->p->fetchData();

        $this->assertTrue($user->can('test', [], false));
        $this->assertFalse($user->can('test_something', [], false));
    }

    public function testPermissionIsDefault()
    {
        $perm = new Permission([
            'code' => 'test',
            'name' => 'Test codename',
            'is_default' => true
        ]);
        $this->assertTrue($perm->isValid());
        $this->assertTrue($perm->save());

        $user = User::objects()->createUser('foo', 'bar', 'admin@admin.com');
        $this->assertEquals(1, $user->permissions->count());

        $this->p->fetchData();

        $this->assertTrue($user->can('test', [], false));
        $this->assertFalse($user->can('test_something', [], false));
    }

    public function testBizRuleViaVariable()
    {
        $perm = new Permission([
            'code' => 'test',
            'name' => 'Test codename',
            'is_default' => true,
            'bizrule' => '$foo=="bar"'
        ]);
        $this->assertTrue($perm->isValid());
        $this->assertTrue($perm->save());

        $user = User::objects()->createUser('foo', 'bar', 'admin@admin.com');
        $this->assertEquals(1, $user->permissions->count());

        $this->p->fetchData();

        $this->assertTrue($this->app->permissions->canBizRule('test', ['foo' => 'bar']));
        $this->assertFalse($this->app->permissions->canBizRule('test', ['foo' => 'default']));

        $this->assertTrue($user->can('test', ['foo' => 'bar'], false));
        $this->assertFalse($user->can('test', ['foo' => 'default'], false));
    }

    public function testBizRuleViaParams()
    {
        $perm = new Permission([
            'code' => 'test',
            'name' => 'Test codename',
            'is_default' => true,
            'bizrule' => '$params["foo"]=="bar"'
        ]);
        $this->assertTrue($perm->isValid());
        $this->assertTrue($perm->save());

        $user = User::objects()->createUser('foo', 'bar', 'admin@admin.com');
        $this->assertEquals(1, $user->permissions->count());

        $this->p->fetchData();

        $this->assertTrue($this->app->permissions->canBizRule('test', ['foo' => 'bar']));
        $this->assertFalse($this->app->permissions->canBizRule('test', ['foo' => 'default']));

        $this->assertTrue($user->can('test', ['foo' => 'bar'], false));
        $this->assertFalse($user->can('test', ['foo' => 'default'], false));
    }
}
