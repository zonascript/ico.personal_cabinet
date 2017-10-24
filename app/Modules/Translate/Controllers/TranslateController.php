<?php

namespace Modules\Translate\Controllers;

use Mindy\Base\Mindy;
use Mindy\Helper\Text;
use Mindy\Locale\Translate;
use Modules\Core\Controllers\BackendController;
use Modules\Translate\Helpers\TranslateHelper;
use Modules\Translate\Tables\LanguageTable;
use Modules\Translate\TranslateModule;

class TranslateController extends BackendController
{
    public $languages = null;

    public function actionIndex()
    {
        $module = $this->getModule();
        $this->addTitle($module->t('Translate'));
        $this->addBreadcrumb($module->t('Translate'));

        if (!$this->languages) {
            $this->languages = [Translate::getInstance()->getLanguage()];
        }

        $table = new LanguageTable($this->languages);
        echo $this->render('translate/index.html', [
            'languages' => $this->languages,
            'table' => $table
        ]);
    }

    public function actionLanguage($lang)
    {
        $this->setBreadcrumbs([
            [
                'name' => TranslateModule::t('Translate'),
                'url' => Mindy::app()->urlManager->reverse('translate:index')
            ],
            [
                'name' => Text::mbUcfirst($lang),
                'url' => Mindy::app()->urlManager->reverse('translate:language', ['lang' => $lang])
            ]
        ]);

        $helper = new TranslateHelper();
        $dictionaries = $helper->collectDictionaries($lang);

        echo $this->render('translate/language.html', [
            'languages' => $this->languages,
            'language' => $lang,
            'dictionaries' => $dictionaries
        ]);
    }

    public function actionProcess()
    {
        $answer = [
            'statement' => 'error',
            'message' => TranslateModule::t('Save error')
        ];
        $helper = new TranslateHelper();

        $r = $this->getRequest();
        $module = $r->post->get('module');
        $dict = $r->post->get('dict');
        $lang = $r->post->get('lang');
        $message = $r->post->get('message');
        $translated = $r->post->get('translated');

        // TODO refactoring
//        $module = isset($_POST['module']) ? $_POST['module'] : null;
//        $dict = isset($_POST['dict']) ? $_POST['dict'] : null;
//        $lang = isset($_POST['lang']) ? $_POST['lang'] : null;
//        $message = isset($_POST['message']) ? $_POST['message'] : null;
//        $translated = isset($_POST['translated']) ? $_POST['translated'] : null;

        if ($module && $dict && $lang && $message && $translated) {
            if ($helper->updateDict($module, $dict, $lang, $message, $translated)) {
                $answer = [
                    'statement' => 'success'
                ];
            }
        }

        echo json_encode($answer);
    }

    public function render($view, array $data = [])
    {
        $data['apps'] = $this->getApplications();
        return parent::render($view, $data);
    }
}
