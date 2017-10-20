<?php
namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Types\Type;
use Xcart\App\Orm\ModelInterface;

class ForeignCharField extends ForeignField
{
    public $namePostfix = '_code';

    /**
     * @var int
     */
    public $length = 255;

    public $sqlType = Type::STRING;


    public function getSqlOptions()
    {
        $options = parent::getSqlOptions();

        /** Inherit from intField */
        unset($options['autoincrement']);
        unset($options['unsigned']);

        return $options;
    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        if ($value instanceof ModelInterface) {
            $value = $value->{$this->getTo()};
        }

        $this->value = $value;
    }
}