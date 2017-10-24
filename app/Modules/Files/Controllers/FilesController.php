<?php

namespace Modules\Files\Controllers;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 22/05/14.05.2014 19:23
 */
use Exception;
use Mindy\Base\Mindy;
use Mindy\Helper\File;
use Modules\Core\Controllers\BackendController;
use Modules\Files\FilesModule;

class FilesController extends BackendController
{
    public function getStorage()
    {
        return Mindy::app()->storage;
    }

    public function actionIndex()
    {
        $module = $this->getModule();
        $this->addBreadcrumb($module->t('Files'));
        $this->addTitle($module->t('Files'));

        $structure = $this->getStorage()->dir($this->getPath());

        echo $this->render('files/list.html', [
            'structure' => $structure,
            'path' => $this->getPath(),
            'upFolder' => $this->getUpFolder(),
            'breadcrumbs' => [
                [
                    'name' => FilesModule::t('Files'),
                    'url' => ['files.index']
                ]
            ]
        ]);
    }

    public function actionApi()
    {
        $action = '';
        if (isset($_POST['action']))
            $action = $_POST['action'];

        switch ($action) {
            case 'delete':
                $this->delete();
                break;
            case 'make':
                $this->make();
                break;
            case 'deleteAll':
                $this->deleteAll();
                break;
            default:
                echo $this->json([
                    'statement' => 'error',
                    'message' => FilesModule::t('Unknown action.')
                ]);
                break;
        }
    }

    /**
     * Makes directory in current path
     */
    public function make()
    {
        $answer = [
            'statement' => 'error',
            'message' => FilesModule::t('Wrong folder name')
        ];

        if (isset($_POST['name']) && $_POST['name']) {
            $name = $this->getPath($_POST['name']);
            if ($this->getStorage()->mkDir($name)) {
                $answer = [
                    'statement' => 'success',
                    'message' => FilesModule::t('The folder was successfully created')
                ];
            } else {
                $answer = [
                    'statement' => 'error',
                    'message' => FilesModule::t('Creating folder problem')
                ];
            }
        }
        echo $this->json($answer);
    }

    /**
     * Загрузка данных
     */
    public function actionUpload()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $temp_dir = 'temp/' . $_GET['flowIdentifier'];
            $chunk_file = $temp_dir . '/' . $_GET['flowFilename'] . '.part' . $_GET['flowChunkNumber'];
            if (file_exists($chunk_file)) {
                header("HTTP/1.0 200 Ok");
            } else {
                header("HTTP/1.0 404 Not Found");
            }
        }

        if (!empty($_FILES)) foreach ($_FILES as $file) {

            // check the error status
            if ($file['error'] != 0) {
                continue;
            }

            // init the destination file (format <filename.ext>.part<#chunk>
            // the file is stored in a temporary directory
            $temp_dir = 'temp/' . $_POST['flowIdentifier'];
            $dest_file = $temp_dir . '/' . $_POST['flowFilename'] . '.part' . $_POST['flowChunkNumber'];

            // create the temporary directory
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }

            // move the temporary file
            if (move_uploaded_file($file['tmp_name'], $dest_file)) {
                // check if all the parts present, and create the final destination file
                $name = $_POST['flowFilename'];

                $fileName = $this->getPath($name);
                $url = $this->createFileFromChunks($temp_dir, $_POST['flowFilename'], $_POST['flowChunkSize'], $_POST['flowTotalSize'], $fileName);
                if ($url) {
                    echo $url;
                }
                echo '';
            }
        }
    }

    /**
     * Сборка файлов из чанка
     * @param $temp_dir
     * @param $fileName
     * @param $chunkSize
     * @param $totalSize
     * @param $finalDestination
     * @return bool
     */
    function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize, $finalDestination)
    {
        // count all the parts of this file
        $total_files = 0;
        foreach (scandir($temp_dir) as $file) {
            if (stripos($file, $fileName) !== false) {
                $total_files++;
            }
        }

        $temp_file = $temp_dir . DIRECTORY_SEPARATOR . $fileName . '.temp';

        // check that all the parts are present
        // the size of the last part is between chunkSize and 2*$chunkSize
        if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {

            // create the final destination file
            if (($fp = fopen($temp_file, 'w')) !== false) {
                for ($i = 1; $i <= $total_files; $i++) {
                    fwrite($fp, file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $fileName . '.part' . $i));
                }
                fclose($fp);
                $resultName = $this->getStorage()->save($finalDestination, file_get_contents($temp_file));
                unlink($temp_file);
            } else {
                return false;
            }

            // rename the temporary directory (to avoid access from other
            // concurrent chunks uploads) and than delete it
            if (rename($temp_dir, $temp_dir . '_UNUSED')) {
                File::removeDirectory($temp_dir . '_UNUSED');
            } else {
                File::removeDirectory($temp_dir);
            }

            return $this->getStorage()->url($resultName);
        }

        return null;
    }

    /**
     * Deletes directory or file
     */
    public function delete()
    {
        $answer = [
            'statement' => 'error',
            'message' => FilesModule::t('Delete problem')
        ];
        if (isset($_POST['name']) && $_POST['name']) {
            $name = $_POST['name'];
            try {
                if ($this->getStorage()->delete($name)) {
                    $answer = [
                        'statement' => 'success',
                        'message' => FilesModule::t('Deleting committed successfully')
                    ];
                }
            } catch (Exception $e) {
            }
        }
        echo $this->json($answer);
    }

    /**
     * Deletes list of files
     */
    public function deleteAll()
    {
        $answer = [
            'statement' => 'success',
        ];
        if (isset($_POST['files']) && $_POST['files'] && is_array($_POST['files'])) {
            $files = $_POST['files'];
            foreach ($files as $file) {
                try {
                    $this->getStorage()->delete($file);
                } catch (Exception $e) {
                };
            }
        }
        echo $this->json($answer);
    }

    /**
     * @param string $filename
     * @return string
     */
    public function getPath($filename = '')
    {
        $path = '';
        if (isset($_GET['path'])) {
            $path = $_GET['path'];
        } elseif (isset($_POST['path'])) {
            $path = $_POST['path'];
        }
        $path = ltrim($path, DIRECTORY_SEPARATOR);

        if ($path) {
            return $path . ($filename ? DIRECTORY_SEPARATOR . $filename : '');
        } else {
            return $filename;
        }
    }

    public function getUpFolder()
    {
        $dir = dirname($this->getPath());
        return $dir != $this->getPath() ? $dir : '';
    }

    public function render($view, array $data = [])
    {
        $data['apps'] = $this->getApplications();
        return parent::render($view, $data);
    }
}
