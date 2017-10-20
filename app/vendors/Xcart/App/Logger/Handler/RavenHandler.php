<?php

namespace Xcart\App\Logger\Handler;

use Monolog\Handler\RavenHandler as MonoRavenHandler;
use Raven_Client;
use Xcart\App\Main\Xcart;

/**
 * Class RavenHandler
 * @package Xcart\App\Logger
 */
class RavenHandler extends ProxyHandler
{
    public $dsn;

    public function getHandler()
    {
        $raven = new Raven_Client($this->dsn);
        return new MonoRavenHandler($raven, $this->getLevel(), $this->bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $options = [];
        $options['level'] = $this->logLevels[$record['level']];
        $options['tags'] = [];
        if (!empty($record['extra']['tags'])) {
            $options['tags'] = array_merge($options['tags'], $record['extra']['tags']);
            unset($record['extra']['tags']);
        }
        if (!empty($record['context']['tags'])) {
            $options['tags'] = array_merge($options['tags'], $record['context']['tags']);
            unset($record['context']['tags']);
        }
        if (!empty($record['context'])) {
            $options['extra']['context'] = $record['context'];
        }
        if (!empty($record['extra'])) {
            $options['extra']['extra'] = $record['extra'];
        }

        $options['extra'] = array_merge([
            'php_version' => phpversion(),
            'app_version' => Xcart::getVersion()
        ], $options['extra']);

        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
            $options['extra']['message'] = $record['formatted'];
            $this->ravenClient->captureException($record['context']['exception'], $options);

            return;
        }

        $this->ravenClient->captureMessage($record['formatted'], [], $options);
    }
}
