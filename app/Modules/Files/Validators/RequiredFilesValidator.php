<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 21/11/16 19:41
 */

namespace Modules\Files\Validators;


use Xcart\App\Validation\RequiredValidator;

class RequiredFilesValidator extends RequiredValidator
{
    public $owner = null;

    public $field = null;

    public function validate($value)
    {
        $qs = $this->owner->{$this->field};
        if ($qs->count() == 0) {
            return $this->message;
        }
        return true;
    }
}