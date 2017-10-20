<?php
namespace Modules\Main\Controllers;

use Modules\Distributor\Models\DistributorModel;
use Modules\Main\Forms\AbstractRequestForm;
use Modules\Main\Forms\InvestorForm;
use Modules\Main\Forms\MediaForm;
use Modules\Main\Forms\PartnersForm;
use Modules\Main\Forms\SellingForm;
use Modules\Main\MainModule;
use Modules\Order\Models\OrderGroupModel;
use Modules\Sites\Models\ListConfigModel;
use Xcart\App\Controller\Controller;
use Xcart\App\Controller\FrontendController;
use Xcart\App\Main\Xcart;

class DefaultController extends FrontendController
{
    public $defaultAction= 'index';

    public function index()
    {

        $storefronts = [];
        echo $this->render('main/index.tpl', [
            'storefronts' => $storefronts,
        ]);
    }

    public function actionORConfirm()
    {
        $request = $this->getRequest();

        if ( $request->get->has('pageid') && $request->get->get('pageid') == 42
            && $request->get->has('s')
            && $request->get->has('o')
            && $request->get->has('m')
        )
        {
            $model = null;
            $oid = $request->get->get('o');
            $mid = $request->get->get('m');
            $om = $oid.$mid;
            $secure = $request->get->get('s');

            if (strpos($secure, 'B-') === 0) {
                $secure_check = text_decrypt($request->get->get('s'));
            }
            else {
                $secure_check = $secure;
                $om = md5($om);
            }

            if ($om && $secure_check == $om) {
                if ($ogModel = OrderGroupModel::objects()->filter(['orderid' => $oid, 'manufacturerid' => $mid])->limit(1)->get()) {
                    $mModel = DistributorModel::objects()->filter(['manufacturerid' => $mid])->get();
                    $model = $ogModel->order;

                    if ($ogModel->dc_status == 'C') {
                        $log = "<B>".$mModel->code.":</B> Distributor confirmed that the order has been received";

                        $ogModel->order_entry_flag = 'D';
                        $ogModel->dc_status = 'L';
                        $ogModel->dc_received_by_distributor_time = time();

                        $ogModel->save();
                    }
                    else {
                        $log = "<B>".$mModel->code.":</B> Distributor confirmed AGAIN that the order has been received";
                    }

                    func_log_order($oid, 'X', $log, 'Distributor ('.$mModel->code.')');
                }
                else {
                    $id = $this->getRequest()->getUserIP();

                    $log = "<B>OrderID:{$oid} | Mid:{$mid}</B> {} confirmed not existed order group";
                    func_log_order($oid, 'X', $log, "User ip ({$id})");
                }

                $this->addTitle('Receipt Confirmation');

                echo $this->render('main/confirmation.tpl', [
                    'model' => $model,
                ]);
                Xcart::app()->end();
            }
        }

        $this->redirect('/');
    }

    public function actionBusinness()
    {
        $this->addTitle(MainModule::t('Business relations'));
        $success_message = MainModule::t('Thank you for contacting us! Your message has been successfully sent.');

        $initial = true;
        $mediaForm = new MediaForm();
        $sellingForm = new SellingForm();
        $investorForm = new InvestorForm();
        $partnersForm = new PartnersForm();

        if ($this->getRequest()->getIsPost()) {

            if (!empty($_POST[$mediaForm->classNameShort()])) {
                $mediaForm->populate($_POST);
                if ($initial = $mediaForm->isValid() && $mediaForm->send()) {
                    Xcart::app()->flash->add($success_message);
                    $this->refresh();
                }
            }

            if (!empty($_POST[$sellingForm->classNameShort()])) {
                $sellingForm->populate($_POST);
                if ($initial = $sellingForm->isValid() && $sellingForm->send()) {
                    Xcart::app()->flash->add($success_message);
                    $this->refresh();
                }
            }

            if (!empty($_POST[$investorForm->classNameShort()])) {
                $investorForm->populate($_POST);
                if ($initial = $investorForm->isValid() && $investorForm->send()) {
                    Xcart::app()->flash->add($success_message);
                    $this->refresh();
                }
            }

            if (!empty($_POST[$partnersForm->classNameShort()])) {
                $partnersForm->populate($_POST);
                if ($initial = $partnersForm->isValid() && $partnersForm->send()) {
                    Xcart::app()->flash->add($success_message);
                    $this->refresh();
                }
            }
        }

        echo $this->render('main/business.tpl', [
            'initial' => $initial,
            'mediaForm' => $mediaForm,
            'sellingForm' => $sellingForm,
            'partnersForm' => $partnersForm,
            'investorForm' => $investorForm,
        ]);
    }
}