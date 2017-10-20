<?php

namespace Xcart\App\Storage\Adapters;

interface AdapterExtInterface
{
    public function __construct($config = []);

    public function getUrl($path);
}