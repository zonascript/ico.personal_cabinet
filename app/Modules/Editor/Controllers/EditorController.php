<?php
namespace Modules\Editor\Controllers;

use Exception;
use Xcart\App\Controller\Controller;
use Xcart\App\Helpers\Text;
use Xcart\App\Main\Xcart;

class EditorController extends Controller
{
    public function getStorage()
    {
        return Xcart::app()->storage->getFilesystem();
    }

    public function getBasePath()
    {
        return 'Editor';
    }

    public function getChangedPath()
    {
        return 'Editor/Changed';
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
        if (!$path || !Text::startsWith($path, $this->getBasePath())) {
            $path = $this->getBasePath();
        }
        $result = [];
        if ($path) {
            $result[] = $path;
        }
        if ($filename) {
            $result[] = $filename;
        }
        return implode($result, DIRECTORY_SEPARATOR);
    }

    public function getUpFolder()
    {
        $dir = dirname($this->getPath());
        return $dir != '.' ? $dir : '';
    }

    public function index()
    {
        $path = $this->getPath();
        if (!$this->getStorage()->has($path)) {
            $this->getStorage()->createDir($path);
        }

        /** @var \League\Flysystem\Directory $structure */
        $dir = $this->getStorage()->get($path);
        $structure = [];
        foreach ($dir->getContents() as $item)
        {
            $key = $item['type'] == 'dir' ? 'directories' : 'files';

            $structure[$key][] = [
                'url' => $this->getStorage()->getAdapter()->getUrl($item['path']),
                'path' => $item['path'],
                'name' => $item['basename'],
            ];
        }

        $field = $this->getRequest()->get->get('field');
        echo $this->render('editor/files/list.tpl', [
            'structure' => $structure,
            'path' => $this->getPath(),
            'upFolder' => $this->getUpFolder(),
            'field' => $field
        ]);
    }

    public function api()
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
                $this->jsonResponse([
                    'statement' => 'error',
                    'message' => "Неизвестное действие"
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
            'message' => 'Некорректное имя папки'
        ];

        if (isset($_POST['name']) && $_POST['name']) {
            $name = $this->getPath($_POST['name']);

            if ($this->getStorage()->createDir($name)) {
                $answer = [
                    'statement' => 'success',
                    'message' => 'Папка успешно создана'
                ];
            } else {
                $answer = [
                    'statement' => 'error',
                    'message' => 'При создании папки произошли проблемы'
                ];
            }
        }
        $this->jsonResponse($answer);
    }

    /**
     * Deletes directory or file
     */
    public function delete()
    {
        $answer = [
            'statement' => 'error',
            'message' => 'При удалении возникла ошибка'
        ];

        if (isset($_POST['name']) && $_POST['name']) {
            $name = $_POST['name'];
            if ($this->getStorage()->has($name)) {
                $item = $this->getStorage()->get($name);

                if ($item->delete()) {
                    $answer = [
                        'statement' => 'success',
                        'message' => 'Удалено'
                    ];
                }
            }
        }

        $this->jsonResponse($answer);
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
                if ($this->getStorage()->has($file)) {
                    $item = $this->getStorage()->get($file);
                    $item->delete();
                }
            }
        }
        $this->jsonResponse($answer);
    }

    /**
     * Загрузка данных
     */
    public function upload()
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
            if ($file['error'] != 0) {
                continue;
            }
            $temp_dir = 'temp/' . $_POST['flowIdentifier'];
            $dest_file = $temp_dir . '/' . $_POST['flowFilename'] . '.part' . $_POST['flowChunkNumber'];
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }
            if (move_uploaded_file($file['tmp_name'], $dest_file)) {
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

    public function changed()
    {
        if (!empty($_FILES)) foreach ($_FILES as $file) {
            $name = $file['name'];
            $aName = explode('.', $name);
            $ext = end($aName);
            $ext = mb_strtolower($ext, 'UTF-8');
            $name = uniqid('', true) . '.' . $ext;

            $dst = $this->getChangedPath() . DIRECTORY_SEPARATOR . $name;
            $storage = $this->getStorage();
            $saved = $storage->write($dst, file_get_contents($file['tmp_name']));
            if ($saved) {
                $this->jsonResponse([
                    'url' => $storage->getAdapter()->getUrl($dst)
                ]);
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
        if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {
            if (($fp = fopen($temp_file, 'w')) !== false) {
                for ($i = 1; $i <= $total_files; $i++) {
                    fwrite($fp, file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $fileName . '.part' . $i));
                }
                fclose($fp);
                $saved = $this->getStorage()->write($finalDestination, file_get_contents($temp_file));
                unlink($temp_file);
            } else {
                return false;
            }
            if (rename($temp_dir, $temp_dir . '_UNUSED')) {
                self::removeDirectory($temp_dir . '_UNUSED');
            } else {
                self::removeDirectory($temp_dir);
            }

            return $this->getStorage()->getAdapter()->getUrl($finalDestination);
        }

        return null;
    }

    public static function removeDirectory($directory)
    {
        $items = glob($directory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_MARK | GLOB_BRACE);
        foreach ($items as $item) {
            if (basename($item) == '.' || basename($item) == '..') {
                continue;
            }
            if (substr($item, -1) == DIRECTORY_SEPARATOR) {
                self::removeDirectory($item);
            } else {
                unlink($item);
            }
        }

        if (is_dir($directory)) {
            rmdir($directory);
        }
    }
}