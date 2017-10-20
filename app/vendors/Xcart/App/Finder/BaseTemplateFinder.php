<?php
namespace Xcart\App\Finder;

abstract class BaseTemplateFinder
{
    public $basePath;

    /**
     * @param $templatePath
     * @return null|string absolute path of template if founded
     */
    abstract public function find($templatePath);

    /**
     * @return array of available template paths
     */
    abstract public function getPaths();
}
