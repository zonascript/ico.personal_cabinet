<?php

namespace Xcart\App\Storage\Files;

use Exception;

/**
 * Class RemoteFile.
 */
class RemoteFile extends ResourceFile
{
    public function __construct($url, $name = null, $tempDir = null)
    {
        if (!$this->urlExists($url)) {
            throw new Exception("File {$url} not found");
        }

        $name = $name ?: basename(strtok($url, '?'));
        parent::__construct(file_get_contents($url), $name);
    }

    public function urlExists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code == 200;
    }
}
