<?php

namespace Xcart\App\Orm\Fields;

use League\Flysystem\File;
use Imagine\Image\ImageInterface;
use Xcart\App\Exceptions\Exception;
use Xcart\App\Exceptions\UnknownMethodException;
use Xcart\App\Exceptions\UnknownPropertyException;
use Xcart\App\Helpers\FileHelper;
use Xcart\App\Traits\ImageProcess;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ImageField.
 */
class ImageField extends FileField
{
    use ImageProcess;

    protected $availableResizeMethods = [
        'resize', 'adaptiveResize', 'adaptiveResizeFromTop'
    ];
    /**
     * Array with image sizes
     * key 'original' is reserved!
     * example:
     * [
     *      'thumb' => [
     *          300,200,
     *          'method' => 'adaptiveResize'
     *      ]
     * ]
     *
     * There are 3 methods resize(THUMBNAIL_INSET), adaptiveResize(THUMBNAIL_OUTBOUND),
     * adaptiveResizeFromTop(THUMBNAIL_OUTBOUND from top)
     *
     * @var array
     */
    public $sizes = [];
    /**
     * Force resize images
     * @var bool
     */
    public $force = false;
    /**
     * Imagine default options
     * @var array
     */
    public $options = [
        'resolution-units' => ImageInterface::RESOLUTION_PIXELSPERINCH,
        'resolution-x' => 72,
        'resolution-y' => 72,
        'jpeg_quality' => 100,
        'quality' => 100,
        'png_compression_level' => 0
    ];
    /**
     * @var array|null
     *
     * File MUST be described relative to "www" directory!
     *
     * example
     * [
     *  'file' => 'static/images/watermark.png',
     *  'position' => [200,100]
     * ]
     *
     * OR
     *
     * [
     *  'file' => 'static/images/watermark.png',
     *  'position' => 'top'
     * ]
     *
     * position can be array [x,y] coordinates or
     * string with one of available position
     * top, top-left, top-right, bottom, bottom-left, bottom-right, left, right, center, repeat
     */
    public $watermark = null;
    /**
     * All supported image types
     * @var array|null
     */
    public $types = ['jpg', 'jpeg', 'png', 'gif'];
    /**
     * Default resize method
     * @var string
     */
    public $defaultResize = 'adaptiveResizeFromTop';
    /**
     * @var bool
     */
    public $storeOriginal = true;
    /**
     * Recreate file if missing
     * @var bool
     */
    public $checkMissing = true;
    /**
     * Cached original
     * @var null | \Imagine\Image\ImagineInterface
     */
    public $_original = null;
    /**
     * Cached original name
     * @var null | string
     */
    public $_originalName = null;

    /**
     * @var array
     */
    public $mimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/svg+xml'
    ];

    /**
     * @var Assert\Image validation settings
     */
    public $minWidth;
    public $maxWidth;
    public $maxHeight;
    public $minHeight;
    public $maxRatio;
    public $minRatio;
    public $allowSquare = true;
    public $allowLandscape = true;
    public $allowPortrait = true;
//    public $detectCorrupted = false;

    /**
     * @return array
     */
    public function getValidationConstraints()
    {
        $constraints = parent::getValidationConstraints();

        if ($this->isRequired() && empty($this->value)) {
            return array_merge($constraints, [
                new Assert\Image([
                    'minWidth' => $this->minWidth,
                    'maxWidth' => $this->maxWidth,
                    'maxHeight' => $this->maxHeight,
                    'minHeight' => $this->minHeight,
                    'maxRatio' => $this->maxRatio,
                    'minRatio' => $this->minRatio,
                    'allowSquare' => $this->allowSquare,
                    'allowLandscape' => $this->allowLandscape,
                    'allowPortrait' => $this->allowPortrait,
//                    'detectCorrupted' => $this->detectCorrupted,
                ]),
            ]);
        }

        return $constraints;
    }


    public function deleteOld()
    {
        parent::deleteOld();

        foreach (array_keys($this->sizes) as $prefix) {
            $path = $this->sizeStoragePath($prefix, $this->getOldValue());

            if ($this->getFilesystem()->has($path)) {
                $this->getFilesystem()->delete($this->getFilesystem()->has($path));
            }
        }
    }

    /**
     * @param ImageInterface $source
     * @param bool           $force
     * @param array|null     $onlySizes - Resize and save only sizes, described in this array
     *
     * @return string
     * @throws \Exception
     * @throws \Xcart\App\Exceptions\Exception
     * @throws \Xcart\App\Exceptions\UnknownMethodException
     * @throws \Xcart\App\Exceptions\UnknownPropertyException
     */
    public function processSource($source, $force = false, $onlySizes = null)
    {
        $ext = pathinfo($this->_originalName, PATHINFO_EXTENSION);
        foreach ($this->sizes as $prefix => $size) {
            if (is_array($onlySizes) && !in_array($prefix, $onlySizes)) {
                continue;
            }

            $method = isset($size['method']) ? $size['method'] : $this->defaultResize;
            $width = isset($size[0]) ? $size[0] : null;
            $height = isset($size[1]) ? $size[1] : null;

            if (!in_array($method, $this->availableResizeMethods)) {
                throw new UnknownMethodException('Unknown resize method: ' . $method);
            }

            if (!$width || !$height) {
                list($width, $height) = $this->imageScale($source, $width, $height);
            }


            $options = isset($size['options']) ? $size['options'] : $this->options;
            $extSize = isset($size['format']) ? $size['format'] : $ext;

            $watermark = isset($size['watermark']) ? $size['watermark'] : $this->watermark;
            if (($width || $height) && $method) {
                $newSource = $this->resize($source->copy(), $width, $height, $method);

                if ($watermark) {
                    $newSource = $this->applyWatermark($newSource, $watermark);
                }

                $this->getFilesystem()->write($this->sizeStoragePath($prefix, $this->getValue()), $newSource->get($extSize, $options));
            }
        }

        if ($this->watermark) {
            $source = $this->applyWatermark($source, $this->watermark);
        }

        return $source->get($ext, $this->options);
    }

    /**
     * @param      $prefix
     * @param null $value
     *
     * @return string
     * @throws \Xcart\App\Exceptions\Exception
     */
    public function sizeStoragePath($prefix, $value)
    {
        $dir = mb_substr_count($value, '/', 'UTF-8') > 0 ? dirname($value) : '';
        $filename = ltrim(mb_substr($value, mb_strlen($dir, 'UTF-8'), null, 'UTF-8'), '/');// TODO not working with cyrillic
        $size = explode('x', $prefix);

        if (strpos($prefix, 'x') !== false && count($size) == 2 && is_numeric($size[0]) && is_numeric($size[1])) {
            $prefix = $this->findSizePrefix($prefix);
        }

        $sizeOptions = isset($this->sizes[$prefix]) ? $this->sizes[$prefix] : [];
        $prefix = $prefix === null ? '' : $this->preparePrefix($prefix);

        if (isset($sizeOptions['format'])) {
            $name = FileHelper::mbPathinfo($filename, PATHINFO_FILENAME);
            $filename = $name . '.' . $sizeOptions['format'];
        }

        return ($dir ? $dir . DIRECTORY_SEPARATOR : '') . $prefix . $filename;
    }

    public function __get($name)
    {
        if (strpos($name, 'url_') === 0) {
            return $this->sizeUrl(str_replace('url_', '', $name));
        }

        throw new UnknownPropertyException("Property '{$name}' not exist in ImageField");
    }

    protected function preparePrefix($prefix)
    {
        return rtrim($prefix, '_') . '_';
    }

    /**
     * @param $prefix
     * @return mixed
     */
    public function sizeUrl($prefix)
    {
        // Original file does not exists, return empty string
        if (!$this->getValue()) {
            return '';
        }

        $value = $this->getValue();
        $path = $this->sizeStoragePath($prefix, $this->getValue());

        if ($this->force || $this->checkMissing && !$this->getFilesystem()->has($path)) {
            $file = $this->getFilesystem()->get($value);

            if ($file instanceof File) {
                $absPath = $this->getFilesystem()->getAdapter()->getPathPrefix() . $file->getPath();

                if ($this->_originalName != $absPath) {
                    $this->_originalName = $absPath;
                    $this->_original = $this->getImagine()->open($absPath);
                }

                $this->processSource($this->_original->copy(), true, [$prefix]);
            }
        }

        return $this->getFilesystem()->getAdapter()->getUrl($path);
    }


    protected function findSizePrefix($prefix, $throw = true)
    {
        $newPrefix = null;
        list($width, $height) = explode('x', trim($prefix, '_'));
        foreach ($this->sizes as $sizePrefix => $size) {
            list($sizeWidth, $sizeHeight) = $size;
            if ($sizeWidth == $width && $sizeHeight == $height) {
                $newPrefix = $sizePrefix;
                break;
            }
        }

        if ($newPrefix === null && $throw) {
            throw new Exception("Prefix with width $width and height $height not found");
        }

        return $newPrefix;
    }

}
