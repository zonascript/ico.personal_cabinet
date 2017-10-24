<?php

namespace Modules\Core\Controllers;

use Mindy\Base\Mindy;
use Mindy\Form\ModelForm;
use Mindy\Helper\Alias;
use Modules\Core\CoreModule;
use Modules\Core\Forms\SettingsForm;

class SettingsController extends BackendController
{
    protected function getSettingsModels()
    {
        $modulesPath = Alias::get('Modules');
        $modules = Mindy::app()->modules;
        $modelsPath = [];
        foreach ($modules as $name => $params) {
            $tmpPath = $modulesPath . '/' . $name . '/Models/';
            $paths = glob($tmpPath . '*Settings.php');
            if (!array_key_exists($name, $modelsPath)) {
                $modelsPath[$name] = [];
            }

            if (is_array($paths)) {
                $modelsPath[$name] = array_merge($modelsPath[$name], array_map(function ($path) use ($name, $tmpPath) {
                    return 'Modules\\' . $name . '\\Models\\' . str_replace('.php', '', str_replace($tmpPath, '', $path));
                }, $paths));
            }
        }
        return $modelsPath;
    }

    protected function reformatModels(array $moduleModels)
    {
        $models = [];
        foreach ($moduleModels as $module => $tmpModels) {
            foreach ($tmpModels as $modelClass) {
                $model = $modelClass::getInstance();
                $classNameShort = $modelClass::classNameShort();
                $models[$modelClass] = [
                    'model' => $model,
                    'form' => new SettingsForm([
                        'model' => $model,
                        'instance' => $model,
                        'key' => "{$module}_{$classNameShort}"
                    ])
                ];
            }
        }
        return $models;
    }

    public function actionIndex()
    {
        $this->addBreadcrumb(CoreModule::t('Settings center'));

        $models = $this->reformatModels($this->getSettingsModels());
        $request = $this->getRequest();
        if ($request->isPost) {
            $success = true;
            foreach ($models as $data) {
                $form = $data['form'];
                if (($request->post->get('key') == $form->key) && ($form->populate($_POST, $_FILES)->isValid() && $form->save()) === false) {
                    $success = false;
                }
            }
            if ($success) {
                $request->flash->success(CoreModule::t('Settings saved successfully'));
                $request->refresh();
            } else {
                $request->flash->error(CoreModule::t('Settings save fail'));
            }
        }

        echo $this->render('core/settings.html', [
            'models' => $models,
        ]);
    }
}
