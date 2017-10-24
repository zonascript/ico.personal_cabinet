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
 * @date 17/11/14.11.2014 15:52
 */

namespace Modules\Mail\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Controllers\FrontendController;
use Modules\Mail\MailModule;
use Modules\Mail\Models\Subscribe;

class SubscribeController extends FrontendController
{
    public function actionSubscribe()
    {
        if (isset($_POST['email']) && !empty($_POST['email'])) {
            $email = $_POST['email'];

            $model = Subscribe::objects()->filter(['email' => $email])->get();
            if ($model === null) {
                $model = new Subscribe(['email' => $email]);

                if ($model->isValid()) {
                    $model->save();
                }
                if ($this->request->getIsAjax()) {
                    echo $this->json([
                        'status' => true,
                        'message' => [
                            'title' => MailModule::t("You are successfully subscribed")
                        ]
                    ]);
                    Mindy::app()->end();
                }
                $this->request->flash->warning(MailModule::t("You are successfully subscribed"));

                Mindy::app()->mail->fromCode('mail.subscribe', Mindy::app()->managers, [
                    'email' => $email
                ]);

                if ($url = $this->getNextUrl()) {
                    $this->redirect($url);
                } else {
                    echo $this->render('mail/subscribe_success.html');
                }
            } else {
                if ($this->request->getIsAjax()) {
                    echo $this->json([
                        'status' => true,
                        'message' => [
                            'title' => MailModule::t("You are already subscribed")
                        ] 
                    ]);
                    Mindy::app()->end();
                }

                $this->request->flash->warning(MailModule::t("You are already subscribed"));

                if ($url = $this->getNextUrl()) {
                    $this->redirect($url);
                } else {
                    echo $this->render('mail/subscribe_failed.html');
                }
            }
        } else {
            $this->request->flash->warning(MailModule::t("Incorrect email address"));
            $this->request->redirect(($url = $this->getNextUrl()) ? $url : '/');
        }
    }

    public function actionUnsubscribe($email, $token)
    {
        $model = Subscribe::objects()->filter([
            'email' => $email,
            'token' => $token
        ])->get();

        if ($model === null) {
            $this->error(404);
        }

        echo $this->render('mail/unsubscribe_success.html');
    }
}
