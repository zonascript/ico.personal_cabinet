<?php
namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class JsonField extends TextField
{
    public function getSqlType()
    {
        return Type::getType(Type::JSON_ARRAY);
    }

    public function getValidationConstraints()
    {
        return array_merge(parent::getValidationConstraints(), [
            new Assert\Callback(function ($value, ExecutionContextInterface $context) {
                if (
                    is_object($value) &&
                    method_exists($value, 'toJson') === false &&
                    method_exists($value, 'toArray') === false
                ) {
                    $context->addViolation('Not json serialize object: %type%', ['%type%' => gettype($value)]);
                }
            })
        ]);
    }

    public function convertToPHPValueSQL($value, AbstractPlatform $platform)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return parent::convertToPHPValueSQL($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return parent::convertToPHPValue($value, $platform);
    }

     public function convertToDatabaseValue($value, AbstractPlatform $platform)
     {
         $opts = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

         if (is_object($value)) {
             if (method_exists($value, 'toJson')) {
                 $value = $value->toJson();
             } else if (method_exists($value, 'toArray')) {
                 $value = json_encode($value->toArray(), $opts);
             } else {
                 $value = json_encode($value, $opts);
             }
         }

         return parent::convertToDatabaseValue($value, $platform);
     }
}