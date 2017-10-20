<?php

namespace Xcart\App\Finder;

class TemplateFinder extends BaseTemplateFinder
{
    public $templatesDir = 'templates';

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param $templatePath
     * @return null|string absolute path of template if founded
     */
    public function find($templatePath)
    {
        $path = join(DIRECTORY_SEPARATOR, [$this->basePath, $this->templatesDir, $templatePath]);
        if(is_file($path)) {
            return $path;
        }

        return null;
    }

    /**
     * @return array of available template paths
     */
    public function getPaths()
    {
        return [
            join(DIRECTORY_SEPARATOR, [$this->basePath, $this->templatesDir])
        ];
    }
}
