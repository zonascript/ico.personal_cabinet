<?php

namespace Modules\Pages\Tests;

use Mindy\Base\Mindy;
use Mindy\Tests\DatabaseTestCase;
use Modules\Pages\Controllers\PageController;
use Modules\Pages\Models\Page;
use Modules\Pages\Sitemap\PageSitemap;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 19/08/14.08.2014 13:05
 */
class PagesTest extends DatabaseTestCase
{
    protected function getModels()
    {
        return [new Page];
    }

    public function testCreate()
    {
        $this->assertEquals('{{%pages_page}}', Page::tableName());

        $model = new Page(['name' => 1, 'is_published' => false]);
        $model->save();

        $this->assertEmptyModel($model);
        $this->assertEquals('1', $model->name);
        $this->assertEquals('/1', $model->url);
        $this->assertEquals(null, $model->parent);

        $children = new Page(['name' => 2, 'parent' => $model, 'is_published' => false]);
        $children->save();

        $this->assertEmptyModel($model);
        $this->assertEquals('2', $children->name);
        $this->assertEquals('/1/2', $children->url);
        $this->assertEquals($model->pk, $children->parent->pk);

        $children->parent = null;
        $children->save();
        $this->assertEmptyModel($model);
        $this->assertNull($children->parent);
        $this->assertEquals('/2', $children->url);

        $children->parent = $model;
        $children->save();
        $this->assertEquals($model->pk, $children->parent->pk);
        $this->assertEquals('/1/2', $children->url);

        $model = Page::objects()->filter(['pk' => 1])->get();

        $newRoot = new Page(['name' => 3, 'is_published' => false]);
        $newRoot->save();

        $model->parent = $newRoot;
        $this->assertTrue($model->save());

        $q = Page::objects()->filter(['pk' => 2])->get();
        $this->assertEquals('/3/1/2', $q->url);
        $this->assertEquals($newRoot->pk, $model->parent->pk);

        $q->parent = $newRoot;
        $this->assertTrue($q->save());
        $this->assertEquals($newRoot->pk, $q->parent->pk);

        $this->assertEquals('/3', $newRoot->url);
        $this->assertEquals('/3/1', $model->url);
        $this->assertEquals('/3/2', $q->url);
    }

    private function assertEmptyModel($model)
    {
        $this->assertEquals('', $model->content);
        $this->assertEquals('', $model->content_short);
        $this->assertInstanceOf('\Mindy\Orm\Fields\ImageField', $model->file);
        $this->assertNull($model->published_at);
        $this->assertNull($model->view);
        $this->assertNull($model->view_children);
        $this->assertNull($model->sorting);
        $this->assertEquals(false, $model->is_index);
        $this->assertEquals(false, $model->is_published);
    }

    public function testRoutes()
    {
        $url = Mindy::app()->urlManager;

        // Test actions
        $this->assertEquals('/pages/foo/bar/', $url->reverse('page.view', ['/foo/bar/']));
        $this->assertEquals('/pages', $url->reverse('page.view', ['']));
        $this->assertEquals('/pages', $url->reverse('page.view', [null]));

        // Test models
        $model = new Page(['name' => '123']);
        $this->assertTrue($model->save());
        $this->assertEquals('/pages/123', $model->url);
        $this->assertEquals('/pages/123', $model->getAbsoluteUrl());

        $model = new Page(['name' => '123', 'is_index' => true]);
        $this->assertTrue($model->save());
        $this->assertEquals('/pages/', $model->getAbsoluteUrl());
    }

    /**
     * @return PageController
     */
    protected function getController()
    {
        $module = Mindy::app()->getModule('Pages');
        return new PageController('page', $module, Mindy::app()->request);
    }

    public function testActions()
    {
        $model = new Page(['name' => '123', 'is_index' => true, 'is_published' => true]);
        $this->assertTrue($model->save());
        $this->assertEquals('/pages/123', $model->getAbsoluteUrl());

        $c = $this->getController();

        $this->assertAction($c, function ($c) {
            /** @var PageController $c */
            $c->actionView();
        }, '<h1>123</h1>');

        $this->assertAction($c, function ($c) {
            /** @var PageController $c */
            $c->actionView('/123');
        }, '<h1>123</h1>');
    }

    public function testSitemap()
    {
        $sitemap = new PageSitemap();
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"/>
', $sitemap->render(false));

        $model = new Page(['name' => '123', 'is_index' => true, 'is_published' => true]);
        $this->assertTrue($model->save());
        $this->assertEquals('/pages/123', $model->getAbsoluteUrl());

        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"><url><loc>/</loc><lastmod>' . date(DATE_W3C, strtotime($model->updated_at ? $model->updated_at : $model->created_at)) . '</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url></urlset>
', $sitemap->render(false));
    }
}