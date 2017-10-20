<?php
namespace Xcart\App\Finder;

use Exception;

/**
 * Class Finder
 * @package Xcart\App\Finder
 */
class Finder
{
    /**
     * @var TemplateFinder[]
     */
    public $finders = [];

    public function __construct(array $finders = [])
    {
        foreach ($finders as $finder) {
            if (($finder instanceof BaseTemplateFinder) === false) {
                throw new Exception("Unknown template finder");
            }
            $this->finders[] = $finder;
        }
    }

    public function find($templatePath)
    {
        $templates = [];
        foreach ($this->finders as $finder) {
            $template = $finder->find($templatePath);
            if ($template !== null) {
                $templates[] = $template;
            }
        }

        // TODO log $templates

        return array_shift($templates);
    }

    public function getPaths()
    {
        $paths = [];
        foreach ($this->finders as $finder) {
            $paths = array_merge($paths, $finder->getPaths());
        }

        return $paths;
    }
}
