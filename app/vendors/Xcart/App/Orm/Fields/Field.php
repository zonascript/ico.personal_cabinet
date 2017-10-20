<?php

namespace Xcart\App\Orm\Fields;

use Closure;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\Type;
use Xcart\App\Helpers\ClassNames;
use Xcart\App\Helpers\Creator;
use Xcart\App\Orm\Model;
use Xcart\App\Orm\ModelInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Xcart\App\Orm\ValidationTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class Field
 * @package Xcart\App\Orm
 */
abstract class Field implements ModelFieldInterface
{
    use ValidationTrait, ClassNames;

    /**
     * @var string|null|false
     */
    public $comment;

    public $field;
    /**
     * @var bool
     */
    public $null = false;
    /**
     * @var null|string|int
     */
    public $default = null;
    /**
     * @var int|string
     */
    public $length = 0;

    public $verboseName = '';

    public $editable = true;

    public $choices = [];

    public $helpText;

    public $unique = false;

    public $primary = false;

    public $autoFetch = false;

    protected $name;

    protected $ownerClassName;

    /**
     * @var \Xcart\App\Orm\Model
     */
    private $_model;

    /**
     * @var array
     */
    protected $validators = [];
    /**
     * @var mixed
     */
    protected $value;

    /**
     * Field constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getValidationConstraints()
    {
        $constraints = [];
        if ($this->isRequired()) {
            $constraints[] = new Assert\NotBlank();
        }

        if ($this->unique) {
            $constraints[] = new Assert\Callback(function ($value, ExecutionContextInterface $context) {
                if ($value === null && $this->null === true) {
                    return;
                }

                if (!$value) {
                    $context->buildViolation('This value should not be blank.')->addViolation();
                    return;
                }

                $maxCount = $this->getModel()->getIsNewRecord() ? 0 : 1;

                if ($this->getModel()->objects()->filter([$this->name => $value])->count() > $maxCount) {
                    $context->buildViolation('The value must be unique')->addViolation();
                }
            });
        }

        if (!empty($this->choices)) {
            $constraints[] = new Assert\Choice([
                'choices' => array_keys($this->choices instanceof Closure ? $this->choices->__invoke() : $this->choices)
            ]);
        }

        return array_merge($constraints, $this->validators);
    }

    /**
     * @return Column
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getColumn()
    {
        $type = $this->getSqlType();
        if ($type) {
            return new Column($this->getAttributeName(), $type, $this->getSqlOptions());
        }
        return null;
    }

    /**
     * @return array
     */
    public function getSqlIndexes()
    {
        $indexes = [];
        if ($this->unique && $this->primary === false) {
            $indexes[] = new Index($this->name . '_idx', [$this->name], true, false);
        }
        return $indexes;
    }

    /**
     * @return array
     */
    public function getSqlOptions()
    {
        $options = [];

        foreach (['length', 'default', 'comment'] as $key) {
            if ($this->{$key} !== null) {
                $options[$key] = $this->{$key};
            }
        }

        if ($this->null) {
            $options['notnull'] = false;
        }

        return $options;
    }

    /**
     * @return string|bool
     */
    public function getAttributeName()
    {
        if (!empty($this->field)) {
            return $this->field;
        }

        return $this->name;
    }

    /**
     * @return Type
     */
    abstract public function getSqlType();

    /**
     * @param ModelInterface $model
     * @return $this
     */
    public function setModel(ModelInterface $model)
    {
        $this->_model = $model;
        return $this;
    }

    public function setModelClass($className)
    {
        $this->ownerClassName = $className;
        return $this;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->getModel()->setAttribute($this->getAttributeName(), $value);
    }

    public function __toString()
    {
        return $this->toText();
    }

    /**
     * @return int|mixed|null|strin
     */
    public function getValue()
    {
        if ($this->getModel() && $value = $this->getModel()->getAttribute($this->getAttributeName())) {
            return $value;
        }
        else if (empty($this->value)) {
            $this->value = $this->null === true ? null : $this->default;
        }

        return $this->value;
    }

    /**
     * @return int|mixed|null|string
     */
    public function getOldValue()
    {
        if (!$this->getModel()->getIsNewRecord() && $value = $this->getModel()->getOldAttribute($this->getAttributeName())) {
            return $value;
        }
        else {
            return null;
        }
    }

    public function cleanValue()
    {
        $this->value = null;
    }

    public function getFormValue()
    {
        return $this->getValue();
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->null === false && is_null($this->default) === true;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getVerboseName()
    {
        if ($this->verboseName) {
            return $this->verboseName;
        } else {
            return str_replace('_', ' ', ucfirst($this->name));
        }
    }

    /**
     * @param ModelInterface $model
     * @param $value
     */
    public function afterInsert(ModelInterface $model, $value)
    {

    }

    /**
     * @param ModelInterface $model
     * @param $value
     */
    public function afterUpdate(ModelInterface $model, $value)
    {

    }

    /**
     * @param ModelInterface $model
     * @param $value
     */
    public function afterDelete(ModelInterface $model, $value)
    {

    }

    /**
     * @param ModelInterface $model
     * @param $value
     */
    public function beforeInsert(ModelInterface $model, $value)
    {

    }

    /**
     * @param ModelInterface $model
     * @param $value
     */
    public function beforeUpdate(ModelInterface $model, $value)
    {

    }

    /**
     * @param ModelInterface $model
     * @param $value
     */
    public function beforeDelete(ModelInterface $model, $value)
    {

    }

    public function toArray()
    {
        return $this->getValue();
    }

    public function toText()
    {
        $value = $this->getValue();
        if (isset($this->choices[$value])) {
            $value = $this->choices[$value];
        }
        return $value;
    }

    public function hasChoices()
    {
        return !empty($this->choices);
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed                                     $value    The value to convert.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The PHP representation of the value.
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $this->getSqlType()->convertToPHPValue($value, $platform);
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed                                     $value    The value to convert.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $this->getSqlType()->convertToDatabaseValue($value, $platform);
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a PHP value.
     *
     * @param string                                    $sqlExpr
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToPHPValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return $this->getSqlType()->convertToPHPValueSQL($sqlExpr, $platform);
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a database value.
     *
     * @param string                                    $sqlExpr
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        if ($sqlExpr === null || $sqlExpr === '') {
            $sqlExpr = null;
        }

        return $this->getSqlType()->convertToDatabaseValueSQL($sqlExpr, $platform);
    }

    /**
     * @param \Xcart\App\Form\Form      $form
     * @param null  $fieldClass
     * @param array $extra
     *
     * @return mixed|null
     */
    public function getFormField($form, $fieldClass = null, array $extra = [])
    {
        if ($this->primary || $this->editable === false) {
            return null;
        }

        if ($fieldClass === null) {
            $fieldClass = $this->choices ? \Xcart\App\Form\Fields\DropDownField::className() : \Xcart\App\Form\Fields\CharField::className();
        } elseif ($fieldClass === false) {
            return null;
        }

        $validators = [];
        if ($form->hasField($this->name)) {
            $field = $form->getField($this->name);
            $validators = $field->validators;
        }
//
//        if (($this->null === false || $this->required) && $this->autoFetch === false && ($this instanceof BooleanField) === false) {
//            $validator = new RequiredValidator;
//            $validator->setName($this->name);
//            $validator->setModel($this);
//            $validators[] = $validator;
//        }
//
//        if ($this->unique) {
//            $validator = new UniqueValidator;
//            $validator->setName($this->name);
//            $validator->setModel($this);
//            $validators[] = $validator;
//        }

        return Creator::createObject(array_merge([
             'class' => $fieldClass,
             'required' => $this->isRequired(),
             'form' => $form,
             'choices' => $this->choices,
             'name' => $this->name,
             'label' => $this->verboseName,
             'hint' => $this->helpText,
             'validators' => array_merge($validators, $this->getValidationConstraints()),
//             'validators' => array_merge($validators, []),
             'value' => $this->default ? $this->default : null

//            'html' => [
//                'multiple' => $this->value instanceof RelatedManager
//            ]
        ], $extra));
    }
}
