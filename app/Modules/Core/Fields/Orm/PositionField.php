<?php
/**
 * Created by PhpStorm.
 * User: antonokulov
 * Date: 13/08/14
 * Time: 07:46
 */
namespace Modules\Core\Fields\Orm;

use Mindy\Orm\Fields\IntField;
use Mindy\Orm\TreeModel;

class PositionField extends IntField
{
    public function onBeforeInsert()
    {
        parent::onBeforeInsert();
        if (!$this->value) {
            $position = $this->getNextPosition();
            $this->setValue($position);
            $this->getModel()->setAttribute($this->getName(), $this->getValue());
        }
    }

    public function getNextPosition()
    {
        $model = $this->getModel();

        $qs = $model::objects();
        if ($model instanceof TreeModel) {
            $parentId = $model->parent_id;
            if ($parentId) {
                $qs = $qs->filter(['parent_id' => $parentId]);
            }
        }
        $max = $qs->max($this->getName());
        return ($max) ? $max+1 : 1;
    }
}