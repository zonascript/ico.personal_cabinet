<?php

namespace Modules\Product\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\IntField;

class ProductHardResellModel extends AutoMetaModel
{
    const HARD_TO_RESELL_YES = 'Y';
    const HARD_TO_RESELL_NO = 'N';
    const HARD_TO_RESELL_UNKNOWN = 'U';

    public static function tableName()
    {
        return 'xcart_products_hard_resell';
    }

    public static function getFields()
    {
        return [
            'product_id' => [
                'class' => IntField::className(),
                'primary' => true
            ],
        ];
    }

    public function getHardToResellStatus()
    {
        $bHardToResell = self::HARD_TO_RESELL_UNKNOWN;
        if ($this->positive_count >= 2 && $this->negative_count == 0) {
            $bHardToResell = self::HARD_TO_RESELL_YES;
        } elseif ($this->positive_count == 0 && $this->negative_count >= 2) {
            $bHardToResell = self::HARD_TO_RESELL_NO;
        } elseif ($this->positive_count > 0 && $this->negative_count > 0) {
            if ($this->positive_count / $this->negative_count < 0.5) {
                $bHardToResell = self::HARD_TO_RESELL_NO;
            } elseif ($this->negative_count / $this->positive_count < 0.5) {
                $bHardToResell = self::HARD_TO_RESELL_YES;
            }
        }
        return $bHardToResell;
    }

}