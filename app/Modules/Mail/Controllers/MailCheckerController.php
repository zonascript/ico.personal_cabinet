<?php

namespace Modules\Mail\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Controllers\FrontendController;
use Modules\Mail\Models\Mail;

class MailCheckerController extends FrontendController
{
    public function allowedActions()
    {
        return ['index'];
    }

    public function actionIndex($uniqueId)
    {
        $model = Mail::objects()->get(['unique_id' => $uniqueId]);
        if ($model !== null) {
            $qb = Mindy::app()->db->getDb()->getQueryBuilder();
            $model->is_read = true;
            $model->readed_at = date($qb->dateTimeFormat);
            $model->save(['is_read', 'readed_at']);
        }

        header("Content-type: image/png");
        echo base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP4Xw8AAoABf5/NhYYAAAAASUVORK5CYII=");
    }
}
