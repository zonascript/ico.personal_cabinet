<?php

/**
 * User: max
 * Date: 15/07/15
 * Time: 19:29
 */

namespace Modules\Files\Controllers;

use Mindy\Base\Mindy;
use Mindy\Helper\Json;
use Modules\Core\Controllers\BackendController;
use Modules\Files\Components\EditorUploader;

class EditorController extends BackendController
{
    /**
     * @return array
     */
    public function getCsrfExempt()
    {
        return ['upload', 'listFiles', 'index'];
    }

    public function upload($action)
    {
        $base64 = "upload";
        switch (htmlspecialchars($action)) {
            case 'uploadimage':
                $config = array(
                    "pathFormat" => $this->config['imagePathFormat'],
                    "maxSize" => $this->config['imageMaxSize'],
                    "allowFiles" => $this->config['imageAllowFiles']
                );
                $fieldName = $this->config['imageFieldName'];
                break;
            case 'uploadvideo':
                $config = array(
                    "pathFormat" => $this->config['videoPathFormat'],
                    "maxSize" => $this->config['videoMaxSize'],
                    "allowFiles" => $this->config['videoAllowFiles']
                );
                $fieldName = $this->config['videoFieldName'];
                break;
            case 'uploadfile':
            default:
                $config = array(
                    "pathFormat" => $this->config['filePathFormat'],
                    "maxSize" => $this->config['fileMaxSize'],
                    "allowFiles" => $this->config['fileAllowFiles']
                );
                $fieldName = $this->config['fileFieldName'];
                break;
        }

        /* 生成上传实例对象并完成上传 */
        $up = new EditorUploader($fieldName, $config, $base64);
        return $up->getFileInfo();
    }

    public function listFiles($action)
    {
        switch ($action) {
            case 'listfile':
                $allowFiles = $this->config['fileManagerAllowFiles'];
                $listSize = $this->config['fileManagerListSize'];
                $path = $this->config['fileManagerListPath'];
                break;
            case 'listimage':
            default:
                $allowFiles = $this->config['imageManagerAllowFiles'];
                $listSize = $this->config['imageManagerListSize'];
                $path = $this->config['imageManagerListPath'];
        }

        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

        /** @var int $size */
        $size = isset($_GET['size']) ? (int)htmlspecialchars($_GET['size']) : (int)$listSize;
        /** @var int $start */
        $start = isset($_GET['start']) ? (int)htmlspecialchars($_GET['start']) : 0;

        $path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "" : "/") . $path;
        $files = $this->getFiles($path, $allowFiles);
        if (!count($files)) {
            return [
                "state" => "no match file",
                "list" => [],
                "start" => $start,
                "total" => count($files)
            ];
        }

        $len = count($files);
        for ($i = min($start + $size, $len) - 1, $list = []; $i < $len && $i >= 0 && $i >= $start; $i--) {
            $list[] = $files[$i];
        }

//        ASC
//        for ($i = $end, $list = []; $i < $len && $i < $end; $i++){
//            $list[] = $files[$i];
//        }

        return [
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ];
    }

    /**
     * @param $path
     * @param array $files
     * @return array
     */
    protected function getFiles($path, $allowFiles, &$files = array())
    {
        if (!is_dir($path)) {
            return null;
        }

        if (substr($path, strlen($path) - 1) != '/') {
            $path .= '/';
        }

        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getFiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(" . $allowFiles . ")$/i", $file)) {
                        $files[] = [
                            'url' => substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                            'mtime' => filemtime($path2)
                        ];
                    }
                }
            }
        }
        return $files;
    }

    public function getConfig()
    {
        $data = preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(__DIR__ . "/config.json"));
        return Json::decode($data);
    }

    public function actionIndex()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : null;

        switch ($action) {
            case 'config':
                $result = $this->getConfig();
                break;
            case 'uploadimage':
            case 'uploadvideo':
            case 'uploadfile':
                $result = $this->upload($action);
                break;
            case 'listimage':
            case 'listfile':
                $result = $this->listFiles($action);
                break;
            default:
                $result = [
                    'state' => 'error'
                ];
                break;
        }

        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $this->json($result) . ')';
            } else {
                echo $this->json([
                    'state' => 'callback参数不合法'
                ]);
                Mindy::app()->end();
            }
        } else {
            echo $this->json($result);
            Mindy::app()->end();
        }
    }
}
