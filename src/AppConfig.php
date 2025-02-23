<?php

namespace Braintacle;

use LogicException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Application config file content.
 *
 * @psalm-type DebugOptions = array{
 *      'display backtrace': bool,
 *      'report missing translations': bool,
 * }
 * @psalm-type DatabaseOptions = array {
 *      'dsn': string,
 * }
 * @property-read DebugOptions $debug
 * @property-read DatabaseOptions $database
 */
class AppConfig
{
    private array $config;

    public function __construct(private Filesystem $filesystem, private string $fileName) {}

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
            $config = parse_ini_string(
                $this->filesystem->readFile($this->fileName),
                true,
                INI_SCANNER_TYPED,
            );
            if ($config === false) {
                throw new RuntimeException('Error parsing config file ' . $this->fileName);
            }
            $this->config = $config;
        }

        return $this->config;
    }
}
