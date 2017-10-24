<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 08/01/15 16:23
 */

namespace Modules\Core\Commands;

use Mindy\Base\Mindy;
use Mindy\Console\ConsoleCommand;
use Mindy\Helper\Alias;
use Mindy\Orm\Migration;
use Mindy\Orm\Sync;
use Mindy\Utils\RenderTrait;
use Modules\Core\Models\Migration as ModelMigration;

class MigrationCommand extends ConsoleCommand
{
    use RenderTrait;

    protected function isToUpMigration($module, $model, $fileName = '', $db = null)
    {
        if (empty($fileName)) {
            return true;
        } else {
            if (strpos($fileName, '_') === false) {
                echo "Name must be a full. Example: Test_123123123." . PHP_EOL;
                die(1);
            }
            list(, $timestamp) = explode('_', $fileName);
            $model = ModelMigration::objects()->using($db)->last()->get([
                'module' => $module,
                'model' => $model
            ]);
            if ($model !== null && $model->timestamp >= $timestamp) {
                return false;
            }

            return true;
        }
    }

    public function actionMigrate($module, $model = null, $name = '', $db = null)
    {
        if ($model === null) {
            $models = Mindy::app()->getModule($module)->getModels();
        } else {
            $className = strtr("\\Modules\\{module}\\Models\\{model}", [
                '{module}' => ucfirst($module),
                '{model}' => ucfirst($model)
            ]);

            if (class_exists($className) === false) {
                echo "Model not found in namespace: " . $className . PHP_EOL;
                exit(1);
            }

            $models = [new $className];
        }

        $path = Alias::get('App.Modules.' . ucfirst($module) . '.Migrations');
        if (!is_dir($path)) {
            echo "Migrations not found" . PHP_EOL;
            die(1);
        }

        $isUp = $this->isToUpMigration($module, $model, $name, $db);

        foreach ($models as $modelInstance) {
            $migration = new Migration($modelInstance, $path);

            $migrationModel = ModelMigration::objects()->last()->get([
                'module' => ucfirst($module),
                'model' => get_class($modelInstance)
            ]);

            $migrations = $migration->getMigrations();
            if (!$isUp) {
                rsort($migrations);
            }
            foreach ($migrations as $migrationFile) {
                $fileName = basename($migrationFile);
                list($name, $timestamp) = explode('_', str_replace('.json', '', $fileName));

                if ($migrationModel) {
                    if ($isUp && $migrationModel->timestamp >= $timestamp) {
                        continue;
                    } else if ($migrationModel->timestamp > $timestamp) {
                        continue;
                    }
                }

                $migrationClassName = strtr("\\Modules\\{module}\\Migrations\\{migration}", [
                    '{module}' => ucfirst($module),
                    '{migration}' => str_replace('.json', '', $fileName)
                ]);

                include_once($path . DIRECTORY_SEPARATOR . str_replace('.json', '', $fileName) . '.php');

                /** @var $migrationInstance \Mindy\Query\Migration */
                $migrationInstance = new $migrationClassName;
                echo "Process: " . str_replace('.json', '', $fileName) . PHP_EOL;

                if ($isUp) {
                    $migrationInstance->up();

                    ModelMigration::objects()->create([
                        'module' => ucfirst($module),
                        'model' => get_class($modelInstance),
                        'timestamp' => $timestamp
                    ]);
                } else {
                    $migrationInstance->down();

                    ModelMigration::objects()->delete([
                        'module' => ucfirst($module),
                        'model' => get_class($modelInstance),
                        'timestamp' => $timestamp
                    ]);
                }

                if (!empty($name) && $fileName == $name) {
                    break;
                }
            }
            echo "Complete" . PHP_EOL;
        }
    }

    public function actionSchemamigration($module, $model = null, $auto = true, $db = null)
    {
        if ($model === null) {
            $models = Mindy::app()->getModule($module)->getModels();
        } else {
            $className = strtr("\\Modules\\{module}\\Models\\{model}", [
                '{module}' => ucfirst($module),
                '{model}' => ucfirst($model)
            ]);

            if (class_exists($className) === false) {
                echo "Model not found in namespace: " . $className . PHP_EOL;
                exit(1);
            }

            $models = [new $className];
        }

        $path = Alias::get('App.Modules.' . ucfirst($module) . '.Migrations');
        if (!is_dir($path)) {
            mkdir($path);
        }

        foreach ($models as $model) {
            $migration = new Migration($model, $path);
            $migration->setDb($db);

            if ($migration->hasChanges() == false) {
                echo "Error: " . $migration->getName() . ". No changes." . PHP_EOL;
                die(1);
            }

            $namespace = strtr("Modules\\{module}\\Migrations", [
                '{module}' => ucfirst($module)
            ]);

            if ($auto) {
                $safeUp = $migration->getSafeUp();
                $safeDown = $migration->getSafeDown();
            } else {
                $safeUp = '';
                $safeDown = '';
            }

            if ($migration->save()) {
                // TODO $db
                $fileName = $path . DIRECTORY_SEPARATOR . $migration->getName();
                $source = $this->generateTemplate($namespace, $migration->getName(), $safeUp, $safeDown);
                file_put_contents($fileName . '.php', $source);

                list(, $timestamp) = explode('_', $migration->getName());

                $model = new ModelMigration;

                $sync = new Sync($model);
                if ($sync->hasTable($model)) {
                    $sync->create();
                }

                echo "Migration created: " . $migration->getName() . PHP_EOL;
            } else {
                echo "Failed to save migration: " . $migration->getName() . ". No changes." . PHP_EOL;
                die(1);
            }
        }
    }

    public function generateTemplate($namespace, $name, $safeUp = '', $safeDown = '')
    {
        return self::renderTemplate('core/migration/migrate.template', [
            'namespace' => $namespace,
            'name' => $name,
            'safeUp' => $safeUp,
            'safeDown' => $safeDown,
        ]);
    }
}
