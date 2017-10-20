<?php

namespace Xcart\App\Form;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;
use Xcart\App\Helpers\Accessors;
use Xcart\App\Helpers\Collection;
use Xcart\App\Helpers\Creator;
use Xcart\App\Helpers\SmartProperties;
use Xcart\App\Main\Xcart;
use Xcart\App\Traits\Configurator;
use Xcart\App\Traits\RenderTrait;
use Xcart\App\Validation\Interfaces\IValidateObject;
use Xcart\App\Validation\Traits\ValidateObject;

/**
 * Class BaseForm
 * @package Mindy\Form
 * @method string asBlock(array $renderFields = [])
 * @method string asUl(array $renderFields = [])
 * @method string asTable(array $renderFields = [])
 */
abstract class BaseForm implements IteratorAggregate, Countable, ArrayAccess, IValidateObject
{
    use Accessors, Configurator, ValidateObject, RenderTrait;

    public $templates = [
        'default' => 'forms/default.tpl',
        'block' => 'core/form/block.tpl',
        'table' => 'core/form/table.tpl',
        'ul' => 'core/form/ul.tpl',
    ];

    public $max = PHP_INT_MAX;
    /**
     * @var string
     */
    public $link;
    /**
     * @var string
     */
    public $defaultTemplateType = 'default';
    /**
     * @var array
     */
    public $exclude = [];
    /**
     * @var array
     */
    private $_extraExclude = [];
    /**
     * @var string
     */
    private $_prefix;
    /**
     * @var int
     */
    private $_id;
    /**
     * @var array
     */
    public static $ids = [];

    /**
     * @var array BaseForm[]
     */
    private $_inlines = [];
    /**
     * @var array
     */
    private $_inlineClasses = [];
    /**
     * @var \Xcart\App\Form\Fields\Field[]
     */
    protected $_fields = [];
    /**
     * @var array
     */
    protected $_renderFields = [];
    /**
     * @var bool
     */
    protected $_saveInlineFailed = false;
    /**
     * @var array BaseForm[]
     */
    private $_inlinesCreate = [];
    /**
     * @var array BaseForm[]
     */
    private $_inlinesDelete = [];
    /**
     * @var BaseForm|ModelForm
     */
    private $_parentForm;

    public function init()
    {
        $this->initFields();
        $this->initInlines();
        $this->setRenderFields(array_keys($this->getFieldsInit()));
    }

    /**
     * @param array $value
     * @return array
     */
    public function setExclude(array $value)
    {
        $this->exclude = $value;
    }

    /**
     * @param array $value
     * @return array
     */
    public function setExtraExclude(array $value)
    {
        $this->_extraExclude = $value;
    }

    /**
     * @return array
     */
    public function getExclude()
    {
        return array_merge($this->_extraExclude, $this->exclude);
    }

    /**
     * @param $prefix
     * @return array
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
    }

    /**
     * @return array
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * @param $owner BaseForm
     */
    public function beforeValidate($owner)
    {
    }

    /**
     * @param $owner BaseForm
     */
    public function afterValidate($owner)
    {
    }

    public function getName()
    {
        return $this->classNameShort();
    }

    public function getFieldsets()
    {
        return [];
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_fields)) {
            return $this->_fields[$name];
        } else {
            return $this->__getInternal($name);
        }
    }

    public function __clone()
    {
        $this->_id = null;

        foreach ($this->_fields as $name => $field) {
            $newField = clone $field;
            $newField->setForm($this);
            $this->_fields[$name] = $newField;
        }
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_fields)) {
            $this->_fields[$name]->setValue($value);
        } else {
            $this->__setInternal($name, $value);
        }
    }

    public function getId()
    {
        if ($this->_id === null) {
            $className = self::className();
            if (array_key_exists($className, self::$ids)) {
                self::$ids[$className]++;
            } else {
                self::$ids[$className] = 0;
            }

            $this->_id = self::$ids[$className];
        }

        return $this->_id;
    }

    /**
     * Initialize fields
     * @void
     */
    public function initFields()
    {
        $prefix = $this->getPrefix();
        $fields = $this->getFields();
        foreach ($fields as $name => $config) {
            if (in_array($name, $this->getExclude())) {
                continue;
            }

            if (!is_array($config)) {
                $config = ['class' => $config];
            }

            $this->_fields[$name] = Creator::createObject(array_merge([
                'name' => $name,
                'form' => $this,
                'prefix' => $prefix,
            ], $config));
        }
    }

    public function __call($name, $arguments)
    {
        $type = strtolower(ltrim($name, 'as'));
        if (isset($this->templates[$type])) {
            $template = $this->getTemplateFromType($type);
            return call_user_func_array([$this, 'render'], array_merge([$template], $arguments));
        } else {
            return $this->__callInternal($name, $arguments);
        }
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function __toString()
    {
        $template = $this->getTemplateFromType($this->defaultTemplateType);
        try {
            return (string)$this->render($template);
        } catch (Exception $e) {
            return (string)$e;
        }
    }

    public function getTemplateFromType($type)
    {
        if (array_key_exists($type, $this->templates)) {
            $template = $this->templates[$type];
        } else {
            throw new Exception("Template type {$type} not found");
        }
        return $template;
    }

    /**
     * @param $template
     * @param array $fields
     * @param null|int $extra count of the extra inline forms for render
     * @return string
     */
    public function render($template = null, array $fields = [], $extra = null)
    {
        if (!$template) {
            $template = $this->getTemplateFromType($this->defaultTemplateType);
        }

        return $this->setRenderFields($fields)->renderInternal($template, [
            'form' => $this,
            'fields' => $fields ?: $this->getRenderFields(),
            'inlines' => $this->renderInlines($extra)
        ]);
    }

    /**
     * @param null|int $extra count of the extra inline forms for render
     * @return array of inline forms
     */
    public function renderInlines($extra = 1)
    {
        $inlines = [];
        foreach ($this->getInlinesInit() as $params) {
            $link = key($params);
            $inline = $params[$link];
            /** @var $inline BaseForm */
            if ($extra <= 0) {
                $extra = 1;
            }

            $forms = [];
            for ($i = 0; $extra > $i; $i++) {
                $forms[] = clone $inline;
            }

            $inlines[$inline->getName()] = $forms;
        }
        return $inlines;
    }

    /**
     * @param $template
     * @param array $params
     * @return string
     */
    public function renderInternal($template, array $params)
    {
        return self::renderTemplate($template, $params);
    }

    public function renderType($templateType, array $fields = [], $extra = null)
    {
        $template = $this->getTemplateFromType($templateType);
        return $this->setRenderFields($fields)->renderInternal($template, [
            'form' => $this,
            'inlines' => $this->renderInlines($extra)
        ]);
    }

    /**
     * Возвращает инициализированные inline формы
     * @return BaseForm[]|ModelForm[]
     */
    public function getInlinesInit()
    {
        return $this->_inlines;
    }

    /**
     * Set fields for render
     * @param array $fields
     * @throws \Exception
     * @return $this
     */
    public function setRenderFields(array $fields = [])
    {
        if (empty($fields)) {
            $fields = array_keys($this->getFieldsInit());
        }
        $this->_renderFields = [];
        $initFields = $this->getFieldsInit();
        foreach ($fields as $name) {
            if (in_array($name, $this->exclude)) {
                continue;
            }
            if (array_key_exists($name, $initFields)) {
                $this->_renderFields[] = $name;
            } else {
                throw new Exception("Field $name not found");
            }
        }
        return $this;
    }

    public function getRenderFields()
    {
        return $this->_renderFields;
    }

    /**
     * Return initialized fields
     * @return \Xcart\App\Form\Fields\Field[]
     */
    public function getFieldsInit()
    {
        return $this->_fields;
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function hasField($attribute)
    {
        return array_key_exists($attribute, $this->_fields);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->isValidInternal() && $this->isValidInlines();
    }

    public function isValidInlines()
    {
        $inlinesCreate = $this->getInlinesCreate();
        $isValid = true;
        foreach ($inlinesCreate as $i => $inline) {
            $ignore = $inline->getPrefix() ? [$inline->link] : [];
            if ($inline->isValidInternal($ignore) === false) {
                $this->addErrors([
                    $inline->classNameShort() => [
                        $i => $inline->getErrors()
                    ]
                ]);

                if ($isValid === true) {
                    $isValid = false;
                }

                if ($this->_saveInlineFailed === false) {
                    $this->_saveInlineFailed = true;
                }
            }
        }
        return $isValid;
    }

    /**
     * @param $attribute
     * @return \Xcart\App\Form\Fields\Field
     */
    public function getField($attribute)
    {
        if ($this->hasField($attribute)) {
            return $this->_fields[$attribute];
        }

        return null;
    }

    public function prepare(array $data, array $files = [], $fixFiles = true)
    {
        return PrepareData::collect($data, $files, $fixFiles);
    }

    /**
     * @param array|Collection $data
     * @param array|Collection $files
     * @return $this
     */
    public function populate($data, $files = [])
    {
        if ($data instanceof Collection) {
            $data = $data->all();
        } else if (!is_array($data)) {
            throw new Exception('$data must be a array');
        }

        $fixFiles = true;
        if ($files instanceof Collection) {
            $fixFiles = false;
            $files = $files->all();
        }

        $tmp = empty($files) ? $data : $this->prepare($data, $files, $fixFiles);
        if (!isset($tmp[$this->classNameShort()])) {
            return $this;
        }

        $data = $tmp[$this->classNameShort()];
        $this->setAttributes($data);
        return $this;
    }

    /**
     * @param BaseForm $form
     * @return $this
     */
    public function setParentForm(BaseForm $form)
    {
        $this->_parentForm = $form;
        return $this;
    }

    /**
     * @return Fields\Field|void
     */
    public function getParentForm()
    {
        return $this->_parentForm;
    }

    /**
     * @param \Xcart\App\Form\BaseForm|\Xcart\App\Form\ModelForm $owner
     * @param array $attributes
     * @return array
     */
    public function beforeSetAttributes($owner, array $attributes)
    {
        return $attributes;
    }

    public function afterOwnerSave($owner)
    {
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setAttributes(array $data)
    {
        $data = $this->beforeSetAttributes($this, $data);

        $fields = $this->getFieldsInit();
        if (empty($data)) {
            foreach ($fields as $field) {
                $field->setValue(null);
            }
        } else {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $fields)) {
                    $fields[$key]->setValue($value);
                }
            }
        }

        // TODO move to ModelForm
        $sourceInlines = $this->getInlinesInit();

        if (count($sourceInlines) > 0) {
            $this->cleanInlinesCreate();
            foreach ($sourceInlines as $params) {
                $link = key($params);
                $sourceInline = $params[$link];
                /** @var $sourceInline BaseForm */
                /** @var $inline BaseForm */
                if (isset($data[$sourceInline->classNameShort()])) {
                    $max = $sourceInline->max;
                    foreach ($data[$sourceInline->classNameShort()] as $i => $item) {
                        if ($i + 1 > $max) {
                            break;
                        }

                        // Form changed or form is new
//                        if (!empty($item['_changed']) || empty($item['_pk'])) {
                            $inline = clone $sourceInline;

                            $item = $inline->beforeSetAttributes($this, $item);

                            if (isset($item['_pk']) && !empty($item['_pk'])) {
                                /** @var $inline ModelForm */
                                $modelClass = $inline->getModel();
                                $model = is_string($modelClass) ? new $modelClass : $modelClass;
                                $instance = $model->objects()->filter(['pk' => $item['_pk']])->get();
                                if ($instance !== null) {
                                    $inline->setInstance($instance);
                                    $inline->setAttributes($item);
                                }
                            } else {
                                $inline->setAttributes($item);
                            }

                            if (!empty($item['_delete'])) {
                                $this->_inlinesDelete[] = $inline;
                            } else {
                                $this->_inlinesCreate[] = $inline;
                            }
//                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        $this->clearErrorsInternal($attribute);
        foreach ($this->getInlinesCreate() as $inline) {
            $inline->clearErrors();
        }
    }

    /**
     * @return \Xcart\App\Form\BaseForm[]
     */
    public function getInlines()
    {
        return [];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        $fields = [];
        foreach ($this->_renderFields as $key) {
            $fields[$key] = $this->_fields[$key];
        }
        return new ArrayIterator($fields);
    }

    public function count()
    {
        return count($this->_renderFields);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_renderFields[] = $value;
        } else {
            $this->_renderFields[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->_renderFields[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_renderFields[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_renderFields[$offset]) ? $this->_renderFields[$offset] : null;
    }

    private function initInlines()
    {
        $inlines = $this->getInlines();

        foreach ($inlines as $params) {
            if (!is_array($params)) {
                throw new Exception("Incorrect inline configuration");
            }
            $link = key($params);
            $className = $params[$link];
            $inline = Creator::createObject([
                'class' => $className,
                'link' => $link,
                'prefix' => $this->classNameShort(),
                'parentForm' => $this,
                'extraExclude' => [$link]
            ]);

            $this->_inlines[][$link] = $inline;
            $this->_inlineClasses[$className::classNameShort()] = $className;
        }
    }

    /**
     * @DEPRECATED
     * @param $name
     */
    public function addExclude($name)
    {
        $this->exclude[] = $name;
    }

    /**
     * @return $this
     */
    public function cleanAttributes()
    {
        $fields = $this->getFieldsInit();
        foreach ($fields as $field) {
            $field->setValue(null);
        }
        return $this;
    }

    /**
     * Return form attributes
     * @return array
     */
    public function getAttributes()
    {
        $attributes = [];
        foreach ($this->getFieldsInit() as $name => $field) {
            $attributes[$name] = $field->getValue();
        }
        return $attributes;
    }

    /**
     * @return ModelForm[]|BaseForm[]
     */
    public function getInlinesCreate()
    {
        return $this->_inlinesCreate;
    }

    /**
     * Clear inlines create variable
     * @void
     */
    public function cleanInlinesCreate()
    {
        $this->_inlinesCreate = [];
    }

    /**
     * @return ModelForm[]|BaseForm[]
     */
    public function getInlinesDelete()
    {
        return $this->_inlinesDelete;
    }

    public function getJsonErrors()
    {
        $data = [];
        foreach ($this->getErrors() as $name => $errors) {
            $data[$this->getField($name)->getHtmlName()] = $errors;
        }
        return $data;
    }
}
