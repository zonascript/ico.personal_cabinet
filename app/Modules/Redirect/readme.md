# Installation

Add `Redirect` to `modules.php`
Add `RedirectMiddleware` to `middleware`

```php
return [
    ...
    'middleware' => [
        'middleware' => [
            'RedirectMiddleware' => [
                'class' => '\Modules\Redirect\Middleware\RedirectMiddleware'
            ],
        ]
    ]
    ...
];
```
