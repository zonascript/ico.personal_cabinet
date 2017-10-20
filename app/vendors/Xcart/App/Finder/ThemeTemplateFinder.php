<?php

namespace Xcart\App\Finder;

class ThemeTemplateFinder extends TemplateFinder
{
    /**
     * @var string
     */
    public $theme;

    public function __construct($basePath, $theme)
    {
        parent::__construct($basePath);
        $this->theme = $theme;
    }

    /**
     * @param $templatePath
     * @return null|string absolute path of template if founded
     */
    public function find($templatePath)
    {
        $path = join(DIRECTORY_SEPARATOR, [$this->basePath, 'themes', $this->theme, $this->templatesDir, $templatePath]);
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
            join(DIRECTORY_SEPARATOR, [$this->basePath, 'themes', $this->theme, $this->templatesDir])
        ];
    }
}
