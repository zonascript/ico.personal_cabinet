<?php
namespace Modules\Sites\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class SiteModel extends Model
{
    private $_config = [];


    public function __toString()
    {
        $str = '';
        $attr = [];
        if (!$this->showInLists()) {
            $attr[] = 'Hidden from stores list';
        }
        if (!$this->isWork()) {
            $attr[] = 'Closed';
        }

        if ($attr) {
            $str = implode(', ', $attr);
            $str = " ({$str})";
        }

        return "[{$this->code}] {$this->domain}{$str}";
    }

    public static function tableName()
    {
        return 'xcart_storefronts';
    }

    public static function getFields()
    {
        return [
            'config' => [
                'class' => HasManyField::className(),
                'modelClass' => SiteConfigModel::className(),
                'link' => ['storefrontid' => 'storefrontid'],
            ],

            'storefrontid' => [
                'class' => AutoField::className(),
            ],
            'code' => [
                'class' => CharField::className(),
                'length' => 10,
                'null' => false,
                'default' => '',
            ],
            'domain' => [
                'class' => CharField::className(),
                'null' => false,
            ],
            'prefix' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'status' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => 'D',
                'choices' => [
                    'Y' => 'Enabled',
                    'E' => 'Service',
                    'D' => 'Disabled'
                ],
            ],
            'orderby' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 10
            ],
        ];
    }

    public function getConfig()
    {
        if (!$this->_config) {

            $config = $this->config->valuesList(['name', 'value']);
            foreach ($config as $item) {
                $this->_config[$item['name']] = $item['value'];
            }
        }

        return $this->_config;
    }


    public function getBaseDomain()
    {
        $domain = strtolower($this->domain);
        
        if (strpos($domain, 'www.') !== false)
        {
            return str_replace('www.', '', $domain);
        }

        return $domain;
    }

    public function isWork()
    {
        if ($config = $this->getConfig()) {
            return (empty($config['shop_closed']) || $config['shop_closed'] == 'N');
        }

        return ($this->status != 'D');
    }

    public function showInLists()
    {
        if ($this->isWork()) {
            if ($config = $this->getConfig()) {
                return (empty($config['search_all_website_show']) || $config['search_all_website_show'] == 'Y');
            }
        }

        return false;
    }

    public static function getAllEnabled()
    {
        $models = static::objects()->all();
        $models = array_filter($models , function($model){ return $model->isWork(); });

        return $models;
    }

}