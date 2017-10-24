<?php

return [
    '.xml' => [
        'name' => 'xml',
        'callback' => '\Modules\Sitemap\Controllers\SitemapController:index'
    ],
    '-{name:c}.xml' => [
        'name' => 'view',
        'callback' => '\Modules\Sitemap\Controllers\SitemapController:view'
    ],
    '/' => [
        'name' => 'html',
        'callback' => '\Modules\Sitemap\Controllers\SitemapController:html'
    ],
    '/json/' => [
        'name' => 'html_json',
        'callback' => '\Modules\Sitemap\Controllers\SitemapController:apiHtml'
    ],
];
