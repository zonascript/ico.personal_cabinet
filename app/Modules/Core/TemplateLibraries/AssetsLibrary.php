<?php
namespace Modules\Core\TemplateLibraries;


use Xcart\App\Exceptions\UnknownPropertyException;
use Xcart\App\Template\TemplateLibrary;

class AssetsLibrary extends TemplateLibrary
{
    public static $assets = [
        'js' => [],
        'css' => [],
    ];

    public static $position = [
        'head', 'footer'
    ];

    /**
     * @kind block
     * @name asset
     * @return void
     */
    public static function aliasAddAsset(array $params = [], $data)
    {
        self::addAsset($params, $data);
    }

    /**
     * @kind block
     * @name add_asset_block
     * @return void
     */
    public static function addAsset(array $params = [], $data)
    {
        $data = trim($data);
        $position = 'footer';

        if (!empty($params['type']) && key_exists($params['type'], self::$assets)) {
            $type = $params['type'];
        }
        else {
            throw new UnknownPropertyException();
        }

        if (!empty($params['position']) && in_array($params['position'], self::$position)) {
            $position = $params['position'];
        }

        if (!empty($params['key'])) {
            self::$assets[$type][$position][$params['key']] = $data;
        }
        else {
            self::$assets[$type][$position][] = $data;
        }
    }

    /**
     * @kind function
     * @name get_assets
     * @return string
     */
    public static function getAssets(array $params = [])
    {
        $position = 'footer';

        if (!empty($params['type']) && key_exists($params['type'], self::$assets)) {
            $type = $params['type'];
        }
        else {
            throw new UnknownPropertyException();
        }

        if (!empty($params['position']) && in_array($params['position'], self::$position)) {
            $position = $params['position'];
        }

        if (!empty(self::$assets[$type][$position])) {
            return implode('', self::$assets[$type][$position]);
        }
        return '';
    }
}