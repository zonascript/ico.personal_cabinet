<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 01/12/14 17:26
 */

namespace Modules\Core\Forms;

use Mindy\Form\ModelForm;
use Mindy\Orm\Model;

class SettingsForm extends ModelForm
{
    protected $model;

    public $key;

    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }
}
