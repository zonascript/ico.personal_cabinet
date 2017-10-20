<?php

namespace Xcart\App\Storage\FileNameHasher;

class MD5NameHasher extends DefaultHasher
{
    /**
     * {@inheritdoc}
     */
    public function hash($fileName)
    {
        return md5($fileName);
    }
}
