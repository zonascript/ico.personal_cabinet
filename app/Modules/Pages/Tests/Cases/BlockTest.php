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
 * @date 20/08/14.08.2014 16:41
 */

namespace Modules\Pages\Tests;

use Mindy\Tests\DatabaseTestCase;
use Modules\Pages\Components\BlockHelper;
use Modules\Pages\Models\Block;
use Modules\User\Models\User;

class BlockTest extends DatabaseTestCase
{
    protected function getModels()
    {
        return [new Block, new User];
    }

    public function testBlock()
    {
        $this->assertEquals('{{%pages_block}}', Block::tableName());

        $model = new Block([
            'name' => 'bar',
            'slug' => 'foo',
            'content' => '123'
        ]);
        $this->assertTrue($model->save());
        $out = BlockHelper::render('foo');
        $this->assertEquals('123', $out);
    }
}
