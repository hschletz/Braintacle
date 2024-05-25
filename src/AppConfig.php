<?php

namespace Braintacle;

use InvalidArgumentException;
use Laminas\Config\Reader\ReaderInterface;

/**
 * Application config file content.
 *
 * @psalm-type DebugOptions array{
 *      'display backtrace': bool,
 *      'report missing translations': bool,
 * }
 * @property-read DebugOptions debug
 */
class AppConfig
{
    private array $config;

    public function __construct(ReaderInterface $reader, string $fileName)
    {
        $this->config = $reader->fromFile($fileName);
    }

    public function __get($name)
    {
        if ($name != 'debug') {
            throw new InvalidArgumentException('Invalid config key: ' . $name);
        }

        return $this->config[$name] ?? [];
    }

    public function getAll()
    {
        return $this->config;
    }
}
