<?php

namespace Xcart\App\Logger;

use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    /**
     * Profile information
     */
    const PROFILE = 700;

    public static function getLevelName($level)
    {
        if ($level === self::PROFILE) {
            return 'PROFILE';
        }

        return parent::getLevelName($level);
    }

    /**
     * Gets all supported logging levels.
     *
     * @return array Assoc array with human-readable level names => level codes.
     */
    public static function getLevels()
    {
        return array_flip(static::$levels + [self::PROFILE => 'PROFILE']);
    }

    /**
     * Adds a log record at the PROFILE level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addProfile($message, array $context = [])
    {
        return $this->addRecord(self::PROFILE, $message, $context);
    }
}
