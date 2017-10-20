<?php
namespace Modules\Sites\Models;

use Mindy\QueryBuilder\Expression;
use Modules\Sites\SitesModule;
use Xcart\App\Helpers\Text;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ForeignCharField;
use Xcart\App\Orm\Fields\ImageField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Model;

/**
 * Class S3ConfigModel
 *
 * @package Modules\Sites\Models
 *
 * @property \Modules\Sites\Models\SiteModel $storefront
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $list_icon
 * @property string $list_image
 * @property integer $position
 */
class ListConfigModel extends Model
{

    public static function tableName()
    {
        return 's3_storefront_config';
    }

    public static function getFields()
    {
        return [
            'id' => AutoField::className(),
            'storefront' => [
                'field' => 'sf_code',
                'class' => ForeignCharField::className(),
                'modelClass' => SiteModel::className(),
                'link' => ['sf_code' => 'code'],
            ],
            'name' => [
                'class' => CharField::className(),
                'null' => true,
            ],
            'description' => [
                'class' => TextField::className(),
                'null' => true,
            ],
            'list_icon' => [
                'class' => ImageField::className(),
                'verboseName' => SitesModule::t('Icon on list'),
            ],
            'list_image' => [
                'class' => ImageField::className(),
                'verboseName' => SitesModule::t('Icon background on list'),
                'sizes' => [
                    'q85' => [
                        176, 176,
                        'method' => 'adaptiveResize',
                        'options' => [
                            'jpeg_quality' => 85,
                            'quality' => 85,
                        ]
                    ],
                    'q75' => [
                        176, 176,
                        'method' => 'adaptiveResize',
                        'options' => [
                            'jpeg_quality' => 75,
                            'quality' => 75,
                        ]
                    ]
                ],
            ],
            'position' => [
                'class' => IntField::className(),
                'verboseName' => SitesModule::t('Position in list'),
                'default' => 9999,
            ],
        ];
    }

    public function __toString()
    {
        return (string)$this->name;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        }

        $name = '';

        $config = $this->storefront->getConfig();

        if (!empty($config['company_name'])) {
            $name = $config['company_name'];

            if (strpos($name, '.') !== false ) {
                $name = substr($name, 0 , strpos($name, '.'));
            }

            $name = Text::camelCaseToUnderscores($name);
            $name = str_replace('_', ' ', ucfirst($name));
            $name = ucwords($name);
        }

        return $name;
    }

    public function beforeSave($owner, $isNew)
    {
        /** @var self $owner */
        if ($this->position == 9999) {
            list($this->position) = static::objects()->limit(1)->valuesList(['position' => new Expression('Max(position) + 1')], true);
        }
    }
}