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
 * @date 11/07/14.07.2014 16:04
 */

namespace Modules\Core\Controllers;


use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;
use Mindy\Base\Mindy;
use Modules\Core\Components\ModuleManager;

class ModulesController extends BackendController
{
    private $_client;

    public function actionInstall($name)
    {
        $version = ModuleManager::install($name);
        if ($version) {
            Mindy::app()->flash->success('Success');
        } else {
            Mindy::app()->flash->error('Failed');
        }

        $this->redirect('core.module_list');
    }

    public function actionUpdate($name, $version = null)
    {
        $version = ModuleManager::update($name, $version);
        if ($version) {
            Mindy::app()->flash->success('Success');
        } else {
            Mindy::app()->flash->error('Failed');
        }

        $this->redirect('core.module_list');
    }

    public function actionView($name)
    {
        $core = Mindy::app()->getModule('Core');
        echo $this->render('core/module_view.html', [
            'data' => $core->update->getInfo($name)
        ]);
    }

    public function actionIndex()
    {
        try {
            $tmp = $this->sendRequest();
            $data = $tmp['objects'];
        } catch(CurlException $e) {
            $data = [];
        }
        echo $this->render('core/module_list.html', ['modules' => $data]);
    }

    protected function getClient()
    {
        if($this->_client === null) {
            $this->_client = new Client(Mindy::app()->getModule('Core')->repositoryUrl);
        }
        return $this->_client;
    }

    /**
     * @param $url
     * @return array
     */
    public function sendRequest()
    {
        return $this->getClient()->get()->send()->json();
    }

    public function render($view, array $data = [])
    {
        $data['apps'] = $this->getApplications();
        return parent::render($view, $data);
    }
}
