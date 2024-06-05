<?php

namespace Braintacle;

use Laminas\Config\Reader\ReaderInterface;
use LogicException;

/**
 * Application config file content.
 *
 * @psalm-type DebugOptions = array{
 *      'display backtrace': bool,
 *      'report missing translations': bool,
 * }
 * @psalm-type DatabaseOptions = array {
 *      'driver': string,
 *      'database': ?string,
 *      'username': ?string,
 *      'password': ?string,
 *      'hostname': ?string,
 *      'port': ?int,
 * }
 * @property-read DebugOptions debug
 * @property-read DatabaseOptions database
 */
class AppConfig
{
    private array $config;

    public function __construct(private ReaderInterface $reader, private string $fileName)
    {
    }

    public function setFile(string $fileName): void
    {
        if (isset($this->config)) {
            throw new LogicException('Cannot set config file. Config is already loaded.');
        }
        $this->fileName = $fileName;
    }

    public function __get($name)
    {
        /** @psalm-suppress UnhandledMatchCondition */
        return match ($name) {
            'database', 'debug' => $this->getAll()[$name] ?? [],
        };
    }

    public function getAll(): array
    {
        if (!isset($this->config)) {
            $this->config = $this->reader->fromFile($this->fileName);
        }

        return $this->config;
    }
}
