<?php
namespace Xcart\App\Storage\Adapters;

use League\Flysystem\Adapter\Local;
use Xcart\App\Helpers\Paths;

class LocalAdapter extends Local implements AdapterExtInterface
{
    private $config = [];

    private $relativeBase;

    protected static $permissions = [
        'file' => [
            'public' => 0664,
            'private' => 0600,
        ],
        'dir' => [
            'public' => 0775,
            'private' => 0700,
        ]
    ];


    public function __construct($config = [])
    {
        $this->config = $config;

        $permissions = self::$permissions;
        if (!empty($config['permissions'])) {
            $permissions = array_replace_recursive($permissions, $config['permissions']);
        }

        $base = Paths::get($config['root']);
        $www = Paths::get('www');

        if (strpos($base, $www) === 0) {
            $this->relativeBase = substr($base, strlen($www));
        }

        parent::__construct($base, LOCK_EX, Local::DISALLOW_LINKS, $permissions);
    }

    public function getUrl($path)
    {
        if ($this->relativeBase)
        {
            return $this->relativeBase .'/'. $path;
        }

        return false;
    }
}