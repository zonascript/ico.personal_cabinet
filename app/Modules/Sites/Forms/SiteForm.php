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
 * @date 15/09/14.09.2014 17:51
 */

namespace Modules\Sites\Forms;

use Mindy\Form\ModelForm;
use Modules\Sites\Models\Site;

class SiteForm extends ModelForm
{
    public function getModel()
    {
        return new Site;
    }
}
