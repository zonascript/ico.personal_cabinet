<?php

namespace Xcart\App\Orm\Fields;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\File as FlyFile;
use League\Flysystem\FilesystemInterface;
use Xcart\App\Form\PrepareData;
use Xcart\App\Main\Xcart;
use Xcart\App\Storage\Adapters\AdapterExtInterface;
use Xcart\App\Storage\FileNameHasher\FileNameHasherInterface;
use Xcart\App\Storage\FileNameHasher\MD5NameHasher;
use Xcart\App\Storage\Files\File;
use Xcart\App\Storage\Files\LocalFile;
use Xcart\App\Storage\Files\ResourceFile;
use Xcart\App\Orm\ModelInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class FileField.
 */
class FileField extends CharField
{
    /**
     * Upload to template, you can use these variables:
     * %Y - Current year (4 digits)
     * %m - Current month
     * %d - Current day of month
     * %H - Current hour
     * %i - Current minutes
     * %s - Current seconds
     * %O - Current object class (lower-based).
     *
     * @var string|callable|\Closure
     */
    public $uploadTo = '%M/%O/%Y-%m-%d';

    /**
     * List of allowed file types.
     *
     * @var array|null
     */
    public $mimeTypes = [];

    /**
     * @var null|int maximum file size or null for unlimited. Default value 2 mb.
     */
    public $maxSize = '5M';

    /**
     * @var callable convert file name
     */
    public $nameHasher;

    public $adapterName;

    /**
     * @var string
     */
    protected $basePath;
    protected $relativePaths = [];

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param FileNameHasherInterface $nameHasher
     */
    public function setNameHasher(FileNameHasherInterface $nameHasher)
    {
        $this->nameHasher = $nameHasher;
    }

    /**
     * @return FileNameHasherInterface
     */
    public function getNameHasher()
    {
        if ($this->nameHasher === null) {
            $this->nameHasher = new MD5NameHasher();
        }

        return $this->nameHasher;
    }

    /**
     * @return array
     */
    public function getValidationConstraints()
    {
        $constraints = [];
        $currentValue = $this->getModel()->getAttribute($this->getAttributeName());

        if ($this->isRequired() && empty($currentValue)) {
            $constraints = [
                new Assert\NotBlank(),
                new Assert\File([
                    'maxSize' => $this->maxSize,
                    'mimeTypes' => $this->mimeTypes,
                ]),
            ];
        }

        return $constraints;
    }

    public function getRelativePath()
    {
        if ($value = $this->getValue())
        {
            $key = $this->adapterName . $value;

            if (empty($this->relativePaths[$key]))
            {
                $adapter = $this->getFilesystem()->getAdapter();

                if ($adapter instanceof AdapterExtInterface) {
                    $this->relativePaths[$key] = $adapter->getUrl($value);
                }
            }

            return $this->relativePaths[$key];
        }

        return null;
    }

    public function getUrl()
    {
        return $this->getRelativePath();
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->getValue();
    }

    /**
     * @return bool
     */
    public function delete()
    {
        return $this->getFilesystem()->delete($this->value);
    }

    /**
     * @return int
     */
    public function size()
    {
        if (empty($this->value)) {
            return 0;
        }
        if ($this->getFilesystem()->has($this->value)) {
            /** @var \League\Flysystem\File $file */
            $file = $this->getFilesystem()->get($this->value);

            return $file->getSize();
        }

        return 0;
    }

    /**
     * @param \Xcart\App\Orm\Model|ModelInterface $model
     * @param $value
     */
    public function afterDelete(ModelInterface $model, $value)
    {
        if ($model->hasAttribute($this->getAttributeName())) {
            $fs = $this->getFilesystem();
            if ($fs->has($value)) {
                $fs->delete($value);
            }
        }
    }

    public function getValue()
    {
        if ($this->value instanceof File || $this->value instanceof UploadedFile) {
            return $this->value;
        }

        return parent::getValue();
    }

    public function setValue($value)
    {
        if ( PrepareData::checkFilesStruct($value) ) {
            if ($value['error'] === UPLOAD_ERR_NO_FILE) {
                $value = $this->getOldValue();
            }
            else {
                $value = new UploadedFile(
                    $value['tmp_name'],
                    $value['name'],
                    $value['type'],
                    (int) $value['size'],
                    (int) $value['error']
                );
            }
        }
        elseif (is_string($value))
        {
            if (strpos($value, 'data:') !== false) {
                list($type, $value) = explode(';', $value);
                list(, $value) = explode(',', $value);
                $value = base64_decode($value);
                $value = new ResourceFile($value, null, null);
            }
            elseif (realpath($value)) {
                $value = new LocalFile(realpath($value));
            }
            elseif ($this->value != $value && $file = $this->getFilesystem()->has($value)) {
                $this->value = $value;
            }
        }

        if ($value === null) {
            $this->value = null;
        }
        elseif ($value instanceof File || $value instanceof UploadedFile) {
            $this->value = $value;
        }
    }

    /**
     * @return array|null
     */
    public function toArray()
    {
        return empty($this->value) ? null : $this->value;
    }

    /**
     * @return string
     */
    public function getUploadTo()
    {
        if (is_callable($this->uploadTo)) {
            return $this->uploadTo->__invoke();
        }
        $model = $this->getModel();

        return strtr($this->uploadTo, [
            '%Y' => date('Y'),
            '%m' => date('m'),
            '%d' => date('d'),
            '%H' => date('H'),
            '%i' => date('i'),
            '%s' => date('s'),
            '%O' => $model->classNameShort(),
            '%M' => $model->getBundleName(),
        ]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_array($value)) {
            $this->setValue($value);
            $value = $this->value;
        }

        if ($value instanceof UploadedFile) {
            $value = $this->saveUploadedFile($value);
        }
        elseif ($value instanceof File) {
            $value = $this->saveFile($value);
        }

        if ($value === false) {
            $value = null;
        }

        if (is_string($value)) {
            $value = $this->normalizeValue($value);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValueSQL($value, AbstractPlatform $platform)
    {
        return $value;
    }

    protected function normalizeValue($value)
    {
        return str_replace('//', '/', $value);
    }

    public function saveUploadedFile(UploadedFile $file)
    {
        if (false === $file->isValid()) {
            return false;
        }

        $contents = file_get_contents($file->getRealPath());

        $path = $this->getNameHasher()->resolveUploadPath(
            $this->getFilesystem(),
            $this->getUploadTo(),
            $file->getClientOriginalName()
        );
        if (!$this->getFilesystem()->write($path, $contents)) {
            throw new Exception('Failed to save file');
        }
        elseif ($this->getOldValue()) {
            $this->deleteOld();
        }

        return $path;
    }

    public function deleteOld()
    {
        if ($this->getFilesystem()->has($this->getOldValue())) {
            $this->getFilesystem()->delete($this->getOldValue());
        }
    }

    public function saveFile(File $file)
    {
        $contents = file_get_contents($file->getRealPath());

        $path = $this->getNameHasher()->resolveUploadPath(
            $this->getFilesystem(),
            $this->getUploadTo(),
            $file->getFilename()
        );

        if (!$this->getFilesystem()->write($path, $contents)) {
            throw new Exception('Failed to save file');
        }

        return $path;
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return \League\Flysystem\Filesystem
     * @throws \Exception
     * @throws \Xcart\App\Exceptions\UnknownPropertyException
     */
    public function getFilesystem()
    {
        if (null === $this->filesystem) {
            return Xcart::app()->storage->getFilesystem($this->adapterName);
        }

        return $this->filesystem;
    }


    public function getFormField($form, $fieldClass = '\Xcart\App\Form\Fields\FileField', array $extra = [])
    {
        return parent::getFormField($form, $fieldClass, $extra);
    }
}
