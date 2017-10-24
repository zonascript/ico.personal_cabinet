<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 06/01/15 15:08
 */

namespace Modules\Files\Controllers;

use Mindy\Base\Mindy;
use Mindy\Helper\Alias;
use Mindy\Helper\File;
use Modules\Core\Controllers\CoreController;

class FlowController extends CoreController
{
    /**
     * Загрузка данных
     */
    public function actionUpload()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $tmpDir = Alias::get('www.media.temp') . '/' . $_GET['flowIdentifier'];
            $chunkFile = $tmpDir . '/' . $_GET['flowFilename'] . '.part' . $_GET['flowChunkNumber'];
            if (file_exists($chunkFile)) {
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
            $tmpDir = Alias::get('www.media.temp') . '/' . $_POST['flowIdentifier'];
            $dstFile = $tmpDir . '/' . $_POST['flowFilename'] . '.part' . $_POST['flowChunkNumber'];

            // create the temporary directory
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }

            // move the temporary file
            if (move_uploaded_file($file['tmp_name'], $dstFile)) {
                // check if all the parts present, and create the final destination file
                $name = $_POST['flowFilename'];

                $fileName = '/tmp/' . $this->getPath($name);
                $url = $this->createFileFromChunks($tmpDir, $_POST['flowFilename'], $_POST['flowChunkSize'], $_POST['flowTotalSize'], $fileName);
                if ($url) {
                    $this->uploadCallback($url);
                    echo $url;
                }
                echo '';
            }
        }
    }

    /**
     * @param string $filename
     * @return string
     */
    public function getPath($filename = '')
    {
        $r = $this->getRequest();
        $path = $r->post->get('path', $r->get->get('path'));

        if (is_null($path)) {
            return $filename;
        } else {
            return ltrim($path, DIRECTORY_SEPARATOR) . ($filename ? DIRECTORY_SEPARATOR . $filename : '');
        }
    }

    public function uploadCallback($url)
    {

    }

    /**
     * Сборка файлов из чанка
     * @param $tmpDir
     * @param $fileName
     * @param $chunkSize
     * @param $totalSize
     * @param $finalDestination
     * @return bool
     */
    function createFileFromChunks($tmpDir, $fileName, $chunkSize, $totalSize, $finalDestination)
    {
        // count all the parts of this file
        $totalFiles = 0;
        foreach (scandir($tmpDir) as $file) {
            if (stripos($file, $fileName) !== false) {
                $totalFiles++;
            }
        }

        $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . $fileName . '.temp';

        // check that all the parts are present
        // the size of the last part is between chunkSize and 2*$chunkSize
        if ($totalFiles * $chunkSize >= ($totalSize - $chunkSize + 1)) {

            // create the final destination file
            if (($fp = fopen($tmpFile, 'w')) !== false) {
                for ($i = 1; $i <= $totalFiles; $i++) {
                    fwrite($fp, file_get_contents($tmpDir . DIRECTORY_SEPARATOR . $fileName . '.part' . $i));
                }
                fclose($fp);
                $this->getStorage()->save($finalDestination, file_get_contents($tmpFile));
                unlink($tmpFile);
            } else {
                return false;
            }

            // rename the temporary directory (to avoid access from other
            // concurrent chunks uploads) and than delete it
            if (rename($tmpDir, $tmpDir . '_UNUSED')) {
                File::removeDirectory($tmpDir . '_UNUSED');
            } else {
                File::removeDirectory($tmpDir);
            }

            return $this->getStorage()->url($finalDestination);
        }

        return null;
    }

    protected function getStorage()
    {
        return Mindy::app()->storage;
    }
}
