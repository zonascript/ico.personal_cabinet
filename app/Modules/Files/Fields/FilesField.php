<?php

namespace Modules\Files\Fields;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\Field;
use Mindy\Helper\JavaScript;
use Mindy\Utils\RenderTrait;

class FilesField extends Field
{
    use RenderTrait;

    public $relatedFileField = 'file';
    public $relatedSortingField = 'position';

    public $uploadUrl;
    public $sortUrl;
    public $deleteUrl;
    public $template = "files/fields/files.html";

    public function getUploadUrl()
    {
        if (!$this->uploadUrl) {
            $this->uploadUrl = Mindy::app()->urlManager->reverse('files:files_upload');
        }
        return $this->uploadUrl;
    }

    public function getSortUrl()
    {
        if (!$this->sortUrl) {
            $this->sortUrl = Mindy::app()->urlManager->reverse('files:files_sort');
        }
        return $this->sortUrl;
    }

    public function getDeleteUrl()
    {
        if (!$this->deleteUrl) {
            $this->deleteUrl = Mindy::app()->urlManager->reverse('files:files_delete');
        }
        return $this->deleteUrl;
    }

    public function getData($encoded = true)
    {
        $model = $this->form->getInstance();
        $data = [
            'uploadUrl' => $this->getUploadUrl(),
            'sortUrl' => $this->getSortUrl(),
            'deleteUrl' => $this->getDeleteUrl(),
            'listId' => $this->getListId(),
            'contentId' => $this->getContentId(),
            'flowData' => [
                'pk' => $model->pk,
                'name' => $this->getName(),
                'class' => $model::className(),
                'fileField' => $this->relatedFileField,
                Mindy::app()->request->csrf->csrfTokenName => Mindy::app()->request->csrf->csrfToken
            ],
            'sortData' => [
                'field' => $this->relatedSortingField,
                'name' => $this->getName(),
                'class' => $model::className(),
                Mindy::app()->request->csrf->csrfTokenName => Mindy::app()->request->csrf->csrfToken
            ],
            'deleteData' => [
                'name' => $this->getName(),
                'class' => $model::className(),
                Mindy::app()->request->csrf->csrfTokenName => Mindy::app()->request->csrf->csrfToken
            ]
        ];
        return ($encoded) ? JavaScript::encode(($data)) : $data;
    }

    public function getQuerySet()
    {
        $qs = $this->form->getInstance()->getField($this->getName())->getManager()->getQuerySet();
        return $qs->order([$this->relatedSortingField]);
    }

    public function renderInput()
    {
        $items = $this->getQuerySet()->all();
        $model = $this->form->getInstance();
 
        return $this->renderTemplate($this->template, [
            'items' => $items,
            'data' => $this->getData(true),
            'id' => $this->uniqueId(),
            'filesId' => $this->getListId(),
            'contentId' => $this->getContentId(),
            'fileField' => $this->relatedFileField,
            'modelPk' => $model->pk
        ]);
    }

    public function uniqueId()
    {
        return $this->getId() . '_' . $this->name;
    }

    public function getListId()
    {
        return $this->uniqueId() . '_files';
    }

    public function getContentId()
    {
        return $this->uniqueId() . '_content';
    }
}