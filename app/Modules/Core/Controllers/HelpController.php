<?php

namespace Modules\Core\Controllers;

use Modules\Core\CoreModule;

class HelpController extends BackendController
{
    public function actionIndex()
    {
        $this->addBreadcrumb(CoreModule::t('Help'));
        $this->addTitle(CoreModule::t('Help'));
        echo $this->render('core/help.html');
    }
}
