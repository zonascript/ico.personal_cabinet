<?php

/**
 * User: max
 * Date: 10/09/15
 * Time: 21:34
 */

namespace Modules\Core\Commands;

use Exception;
use Mindy\Console\ConsoleCommand;
use Mindy\Helper\Alias;
use Mindy\Utils\RenderTrait;

class CrudCommand extends ConsoleCommand
{
    use RenderTrait;

    /**
     * @param $module
     * @param string $template
     * @throws Exception
     */
    public function actionModule($module, $template = "core/crud/module.template")
    {
        $module = ucfirst($module);
        $template = $this->renderTemplate($template, [
            'module' => $module,
        ]);

        $path = Alias::get('Modules.' . $module);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $this->write($path . DIRECTORY_SEPARATOR . $module . 'Module.php', $template);
    }

    /**
     * @param $module
     * @param $name
     * @param string $template
     * @throws Exception
     */
    public function actionModel($module, $name, $template = "core/crud/model.template")
    {
        $module = ucfirst($module);
        $name = ucfirst($name);
        $template = $this->renderTemplate($template, [
            'module' => $module,
            'name' => $name
        ]);

        $path = Alias::get('Modules.' . $module . '.Models');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $this->write($path . DIRECTORY_SEPARATOR . $name . '.php', $template);
    }

    /**
     * @param $module
     * @param $name
     * @param string $template
     * @throws Exception
     */
    public function actionController($module, $name, $template = "core/crud/controller.template")
    {
        $module = ucfirst($module);
        $name = ucfirst($name);
        $template = $this->renderTemplate($template, [
            'module' => $module,
            'name' => $name
        ]);

        $path = Alias::get('Modules.' . $module . '.Controllers');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $this->write($path . DIRECTORY_SEPARATOR . $name . 'Controller.php', $template);
    }

    protected function write($path, $content)
    {
        if ((file_put_contents($path, $content) >= 0) == false) {
            throw new Exception("Failed to write " . basename($path));
        }
    }
}
