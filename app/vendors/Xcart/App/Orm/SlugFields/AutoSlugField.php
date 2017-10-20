<?php

namespace Xcart\App\Orm\SlugFields;

use Mindy\QueryBuilder\Expression;
use Xcart\App\Orm\ModelInterface;

/**
 * Class AutoSlugField.
 */
class AutoSlugField extends AbstractSlugField
{
    /**
     * @var string|null
     */
    protected $oldValue;

    /**
     * Internal event.
     *
     * @param \Xcart\App\Orm\TreeModel|ModelInterface $model
     * @param $value
     */
    public function beforeInsert(ModelInterface $model, $value)
    {
        if (empty($value)) {
            $slug = $this->createSlug($model->getAttribute($this->source));
        } else {
            $slug = $this->getLastSegment($value);
        }

        if ($model->parent) {
            $slug = $model->parent->getAttribute($this->getAttributeName()).'/'.ltrim($slug, '/');
        }

        $model->setAttribute($this->getAttributeName(), $this->uniqueUrl(ltrim($slug, '/')));
    }

    public function beforeValidate()
    {
        $this->value = $this->getSlug($this->getModel(), $this->getValue());
    }

    /**
     * Internal event.
     *
     * @param \Xcart\App\Orm\TreeModel|ModelInterface $model
     * @param                                         $value
     *
     * @return string
     */
    public function getSlug(ModelInterface $model, $value)
    {
        if (empty($value)) {
            $slug = $this->createSlug($model->getAttribute($this->source));
        } else {
            $slug = $this->getLastSegment($value);
        }

        if ($model->parent) {
            $slug = implode('/', [
                $this->getParentSegment($model->parent->getAttribute($this->getAttributeName())),
                $slug,
            ]);
        }

        $slug = $this->uniqueUrl(ltrim($slug, '/'), 0, $model->pk);

        return $slug;
    }

    /**
     * @param $slug
     *
     * @return string
     */
    protected function getLastSegment($slug)
    {
        if (strpos($slug, '/') === false) {
            return $slug;
        }

        return substr($slug, strrpos($slug, '/', -1) + 1);
    }

    /**
     * @param $slug
     *
     * @return string
     */
    protected function getParentSegment($slug)
    {
        if (strpos($slug, '/') === false) {
            return $slug;
        }

        return substr($slug, 0, strrpos($slug, '/', -1));
    }

    /**
     * Internal event.
     *
     * @param \Xcart\App\Orm\TreeModel|ModelInterface $model
     * @param $value
     */
    public function beforeUpdate(ModelInterface $model, $value)
    {
        if (empty($value)) {
            $slug = $this->createSlug($model->getAttribute($this->source));
        } else {
            $slug = $this->getLastSegment($value);
        }

        if ($model->parent) {
            $slug = implode('/', [
                $this->getParentSegment($model->parent->getAttribute($this->getAttributeName())),
                $slug,
            ]);
        }

        $slug = $this->uniqueUrl(ltrim($slug, '/'), 0, $model->pk);

        $conditions = [
            'lft__gte' => $model->getAttribute('lft'),
            'rgt__lte' => $model->getAttribute('rgt'),
            'root' => $model->getAttribute('root'),
        ];

        $connection = $model->getConnection();

        $attributeValue = $model->getOldAttribute($this->getAttributeName());
        if (empty($attributeValue)) {
            $attributeValue = $model->getAttribute($this->getAttributeName());
        }
        $expr = 'REPLACE('. $connection->quoteIdentifier($this->getAttributeName()).', '.$connection->quote($attributeValue).', '.$connection->quote($slug).')';

        $qs = $model->objects()->filter($conditions);
        $qs->update([
            $this->getAttributeName() => new Expression($expr),
        ]);

        $model->setAttribute($this->getAttributeName(), $slug);
    }
}
