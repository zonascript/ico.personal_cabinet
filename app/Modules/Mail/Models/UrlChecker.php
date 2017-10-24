<?php

/**
 * User: max
 * Date: 31/08/15
 * Time: 16:38
 */

namespace Modules\Mail\Models;

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\Mail\MailModule;

class UrlChecker extends Model
{
    public static function getFields()
    {
        return [
            'mail' => [
                'class' => ForeignField::className(),
                'modelClass' => Mail::className(),
                'verboseName' => MailModule::t('Mail')
            ],
            'url' => [
                'class' => CharField::className(),
                'verboseName' => MailModule::t('Url')
            ]
        ];
    }

    public function __toString()
    {
        return (string)strtr('{mail} {url}', [
            '{mail}' => (string)$this->mail,
            '{url}' => $this->url
        ]);
    }

    public function getAdminNames($instance = null)
    {
        $module = $this->getModule();
        $cls = self::classNameShort();
        $name = self::normalizeName($cls);
        if ($instance) {
            $updateTranslate = $module->t('Update ' . $name . ': {name}', ['{name}' => (string)$instance]);
        } else {
            $updateTranslate = $module->t('Update ' . $name);
        }
        return [
            $module->t('Url checker'),
            $module->t('Create ' . $name),
            $updateTranslate,
        ];
    }
}
