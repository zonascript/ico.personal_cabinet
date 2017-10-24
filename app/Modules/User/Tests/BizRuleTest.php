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
 * @date 23/10/14.10.2014 19:47
 */

namespace Modules\User\Tests;

use Mindy\Tests\TestCase;

class BizRuleTest extends TestCase
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

    public function testEmpty()
    {
        $this->assertTrue($this->p->executeBizRule('', ['foo' => 'bar']));
        $this->assertTrue($this->p->executeBizRule(''));
        $this->assertTrue($this->p->executeBizRule(null, ['foo' => 'bar']));
        $this->assertTrue($this->p->executeBizRule(null));
    }

    public function testViaVariable()
    {
        $this->assertTrue($this->p->executeBizRule('$foo=="bar"', ['foo' => 'bar']));
        $this->assertFalse($this->p->executeBizRule('$foo=="bar"', ['foo' => 'default']));
    }

    public function testViaParams()
    {
        $this->assertTrue($this->p->executeBizRule('$params["foo"]=="bar"', ['foo' => 'bar']));
        $this->assertFalse($this->p->executeBizRule('$params["foo"]=="bar"', ['foo' => 'default']));
    }

    public function testGlobal()
    {
        $this->assertTrue($this->p->executeBizRule('\Mindy\Base\Mindy::app()->hasComponent("auth")'));
    }

    public function testReturn()
    {
        $this->assertTrue($this->p->executeBizRule('return true'));
        $this->assertTrue($this->p->executeBizRule('return []'));
        $this->assertFalse($this->p->executeBizRule('return false'));
    }
}
