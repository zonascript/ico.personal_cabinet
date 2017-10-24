<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 24/03/15 16:30
 */

namespace Modules\Mail\Controllers;

use Mindy\Base\Mindy;
use Mindy\Validation\EmailValidator;
use Modules\Core\Controllers\ApiBaseController;
use Modules\Mail\MailModule;
use Modules\Mail\Models\Subscribe;

class ApiController extends ApiBaseController
{
    public function actionSubscribe()
    {
        $r = $this->getRequest();
        if ($r->getIsPost() && isset($_POST['email'])) {
            $email = $_POST['email'];
            if ($this->validateEmail($email)) {
                $model = Subscribe::objects()->filter(['email' => $email])->get();
                if ($model === null) {
                    $model = new Subscribe(['email' => $email]);
                    if ($model->isValid()) {
                        $model->save();
                    }
                    Mindy::app()->mail->fromCode('mail.subscribe', Mindy::app()->managers, [
                        'email' => $email
                    ]);
                    echo $this->json([
                        'success' => true,
                        'message' => MailModule::t("You are successfully subscribed")
                    ]);
                    Mindy::app()->end();
                } else {
                    echo $this->json([
                        'success' => true,
                        'message' => MailModule::t("You are already subscribed")
                    ]);
                    Mindy::app()->end();
                }
            } else {
                echo $this->json([
                    'success' => false,
                    'errors' => [
                        'email' => [
                            MailModule::t("Incorrect email address")
                        ]
                    ]
                ]);
                Mindy::app()->end();
            }
        } else {
            echo $this->json([
                'success' => false
            ]);
            Mindy::app()->end();
        }
    }

    protected function validateEmail($value)
    {
        $validator = new EmailValidator(true);
        return $validator->validate($value);
    }
}
