<?php

namespace Modules\Core;

use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Mindy\Helper\Alias;
use Mindy\Helper\Json;
use Mindy\Locale\Translate;
use Modules\Core\Models\UserLog;
use Modules\User\Models\Session;

class CoreModule extends Module
{
    /**
     * @var string
     */
    public $repositoryUrl = 'http://store.mindy-cms.com/api/v1/package';
    /**
     * @var \Mindy\Update\Update
     */
    public $update;

    public static function preConfigure()
    {
        $app = Mindy::app();

        $tpl = $app->template;
        $tpl->addHelper('get_static_version', function () {
            $filePath = Alias::get('www.static') . '/package.json';
            $content = file_get_contents($filePath);
            $data = JSON::decode($content);
            return $data['version'];
        });
        $tpl->addHelper('t', function ($text, $category, $params = []) {
            if ($category !== 'app' && !strpos($category, '.')) {
                $category .= '.main';
            }
            $findCategory = explode('.', $category);
            $moduleNameRaw = ucfirst($findCategory[0]);
            if (Mindy::app()->hasModule($moduleNameRaw)) {
                $module = Mindy::app()->getModule($moduleNameRaw);
                $moduleName = get_class($module) . '.' . $findCategory[1];
                return Mindy::t($moduleName, $text, $params);
            } else {
                return $text;
            }
        });
        $tpl->addHelper('convert_base64', ['\Modules\Mail\Helper\MailHelper', 'convertToBase64']);
        $tpl->addHelper('ucfirst', ['\Mindy\Helper\Text', 'mbUcfirst']);
        $tpl->addHelper('debug_panel', ['\Modules\Core\Components\DebugPanel', 'render']);
        $tpl->addHelper('param', ['\Modules\Core\Components\ParamsHelper', 'get']);
        $tpl->addHelper('humanizeDateTime', ['\Modules\Core\Components\Humanize', 'humanizeDateTime']);
        $tpl->addHelper('locale', function() use ($app) {
            return $app->locale;
        });
        $tpl->addHelper('humanizeSize', ['\Modules\Core\Components\Humanize', 'humanizeSize']);
        $tpl->addHelper('humanizePrice', ['\Modules\Core\Components\Humanize', 'numToStr']);
        $tpl->addHelper('limit', ['\Mindy\Helper\Text', 'limit']);
        $tpl->addHelper('strtotime', 'strtotime');
        $tpl->addHelper('time', 'time');
        $tpl->addHelper('is_file', 'is_file');
        $tpl->addHelper('d', 'd');
        $tpl->addHelper('locale_date', function ($timestamp, $format = 'd MMMM yyyy') {
            return Translate::getInstance()->getDateFormatter()->format($format, $timestamp);
        });
        $tpl->addHelper('method_exists', function ($obj, $name) {
            return method_exists($obj, $name);
        });
        $tpl->addHelper('user_actions', function ($by = 10) {
            return UserLog::objects()->limit($by)->order(['-created_at'])->all();
        });

        $signal = $app->signal;
        $signal->handler('\Mindy\Orm\Model', 'afterSave', [self::className(), 'afterSaveModel']);
        $signal->handler('\Mindy\Orm\Model', 'afterDelete', [self::className(), 'afterDeleteModel']);
    }

    public static function recordActionInternal($owner, $text)
    {
        if (defined('MINDY_TESTS')) {
            return;
        }

        $user = Mindy::app()->getUser();
        if (
            in_array($owner->className(), [UserLog::class, Session::class]) ||
            $user->is_staff ||
            $user->is_superuser
        ) {
            return;
        } else {
            $url = method_exists($owner, 'getAbsoluteUrl') ? $owner->getAbsoluteUrl() : null;
            $message = strtr('{model} {url} ' . $text, [
                '{model}' => $owner->classNameShort()
            ]);
            $app = Mindy::app();
            $module = $owner->getModule();
            UserLog::objects()->create([
                'user' => $app->getUser()->getIsGuest() ? null : $app->getUser(),
                'module' => $owner->getModuleName(),
                'model' => $owner->classNameShort(),
                'url' => $url,
                'ip' => $app->getUser()->getIp(),
                'name' => (string)$owner,
                'message' => $module->t($message, [
                    '{url}' => $url ? "<a href='" . $owner->getAbsoluteUrl() . "'>" . (string)$owner . "</a>" : (string)$owner,
                ])
            ]);
        }
    }

    public static function afterSaveModel($owner, $isNew)
    {
        self::recordActionInternal($owner, $isNew ? 'was created' : 'was updated');
    }

    public static function afterDeleteModel($owner)
    {
        self::recordActionInternal($owner, 'was deleted');
    }

    public function init()
    {
        // $this->update = new Update([
        //     'repoUrl' => $this->repositoryUrl,
        //     'installDir' => Alias::get('application.Modules'),
        //     'downloadDir' => Alias::get('application.runtime'),
        // ]);
    }

    public function getMenu()
    {
        return [
            'name' => $this->getName(),
            'items' => [
//                 [
//                     'name' => self::t('Modules'),
//                     'url' => 'core:module_list'
//                 ],
                [
                    'name' => self::t('Settings'),
                    'url' => 'core:settings'
                ],
//                [
//                    'name' => self::t('Help'),
//                    'url' => 'core:help-online'
//                ]
            ]
        ];
    }

    public function getVersion()
    {
        return '0.4';
    }
}
