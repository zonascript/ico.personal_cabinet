<?php

namespace Modules\Files\Controllers;

use Mindy\Base\Mindy;
use Mindy\Exception\Exception;
use Mindy\Helper\Alias;
use Modules\Core\Controllers\BackendController;
use Mindy\Storage\Files\LocalFile;

class UploadController extends BackendController
{
    public $ds = DIRECTORY_SEPARATOR;
    public $tempDir = 'temp';

    /**
     * Загрузка данных
     */
    public function actionUpload()
    {
        $this->tempDir = Alias::get('www') . '/temp';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $temp_dir = $this->tempDir . '/' . $_GET['flowIdentifier'];
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
            $temp_dir = $this->tempDir . '/' . $_POST['flowIdentifier'];
            $dest_file = $temp_dir . '/' . $_POST['flowFilename'] . '.part' . $_POST['flowChunkNumber'];

            // create the temporary directory
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }

            // move the temporary file
            if (move_uploaded_file($file['tmp_name'], $dest_file)) {
                // check if all the parts present, and create the final destination file
                $name = $_POST['flowFilename'];
                $fileName = join($this->ds, [$this->tempDir, $name]);
                $this->createFileFromChunks($temp_dir, $_POST['flowFilename'], $_POST['flowChunkSize'], $_POST['flowTotalSize'], $fileName);
                echo "/" . $fileName;
            }
        }
    }

    /**
     * Рекурсивное удаление папки
     * @param $dir
     * @return bool|null
     */
    function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            return rmdir($dir);
        }
        return null;
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

        // check that all the parts are present
        // the size of the last part is between chunkSize and 2*$chunkSize
        if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {

            // create the final destination file
            if (($fp = fopen($finalDestination, 'w')) !== false) {
                for ($i = 1; $i <= $total_files; $i++) {
                    fwrite($fp, file_get_contents($temp_dir . '/' . $fileName . '.part' . $i));
                }
                fclose($fp);
                $this->saveModel($finalDestination);
            } else {
                return false;
            }

            // rename the temporary directory (to avoid access from other
            // concurrent chunks uploads) and than delete it
            if (rename($temp_dir, $temp_dir . '_UNUSED')) {
                $this->rrmdir($temp_dir . '_UNUSED');
            } else {
                $this->rrmdir($temp_dir);
            }
        }
    }

    protected function saveModel($filename)
    {
        $pk = isset($_POST['pk']) ? $_POST['pk'] : null;
        $class = isset($_POST['class']) ? $_POST['class'] : null;

        $name = isset($_POST['name']) ? $_POST['name'] : null;
        $fileField = isset($_POST['fileField']) ? $_POST['fileField'] : null;

        $model = null;

        if ($pk) {
            $model = $class::objects()->get(['pk' => $pk]);
        }

        if ($model && $name) {
            $manager = $model->{$name};
            $relatedClass = $manager->getModel()->className();
            $related = new $relatedClass();

            $related->{$fileField} = $filename;

            $related->{$manager->to} = $pk;

            $related->save();
        }

        try {
            unlink($filename);
        } catch (\Exception $e) {

        }
    }

    public function actionSort()
    {
        $pkList = isset($_POST['pk']) ? $_POST['pk'] : null;
        $field = isset($_POST['field']) ? $_POST['field'] : null;

        $class = isset($_POST['class']) ? $_POST['class'] : null;
        $name = isset($_POST['name']) ? $_POST['name'] : null;

        if ($pkList && $field && $class && $name) {
            $model = new $class;
            $manager = $model->{$name};
            $relatedClass = $manager->getModel()->className();
            foreach($pkList as $position => $pk) {
                if ($related = $relatedClass::objects()->get(['pk' => $pk])){
                    $related->{$field} = $position;
                    $related->save([$field]);
                };
            }
        }
    }

    public function actionDelete()
    {
        $pk = isset($_POST['pk']) ? $_POST['pk'] : null;
        $class = isset($_POST['class']) ? $_POST['class'] : null;
        $name = isset($_POST['name']) ? $_POST['name'] : null;
        if ($pk && $class && $name) {
            $model = new $class;
            $manager = $model->{$name};
            $relatedClass = $manager->getModel()->className();
            if ($related = $relatedClass::objects()->get(['pk' => $pk])){
                $related->delete();
            }
        }
    }
}
