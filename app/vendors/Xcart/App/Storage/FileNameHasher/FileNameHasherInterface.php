<?php

namespace Xcart\App\Storage\FileNameHasher;

use League\Flysystem\FilesystemInterface;

interface FileNameHasherInterface
{
    /**
     * @param string $name
     *
     * @return string
     */
    public function hash($name);

    /**
     * @param FilesystemInterface $filesystem
     * @param $uploadTo
     * @param $name
     *
     * @return string
     */
    public function resolveUploadPath(FilesystemInterface $filesystem, $uploadTo, $name);
}
