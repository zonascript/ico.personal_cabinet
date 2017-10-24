<?php

/**
 * User: max
 * Date: 31/08/15
 * Time: 16:40
 */

namespace Modules\Mail\Admin;

use Modules\Admin\Components\ModelAdmin;
use Modules\Mail\MailModule;
use Modules\Mail\Models\UrlChecker;

class UrlCheckerAdmin extends ModelAdmin
{
    public function getActionsList()
    {
        return [];
    }

    /**
     * Verbose names for custom columns
     * @return array
     */
    public function verboseNames()
    {
        return [
            'mail__email' => MailModule::t('Mail')
        ];
    }

    public function getCanCreate()
    {
        return false;
    }

    public function getColumns()
    {
        return ['mail__email', 'url'];
    }

    /**
     * @return \Mindy\Orm\Model
     */
    public function getModel()
    {
        return new UrlChecker;
    }
}
