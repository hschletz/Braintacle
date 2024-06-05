<?php

namespace Braintacle\Database;

use Braintacle\AppConfig;
use Laminas\Db\Adapter\Adapter;

/**
 * @codeCoverageIgnore
 */
class AdapterFactory
{
    public function __construct(private AppConfig $appConfig)
    {
    }

    // Return type is Adapter, not AdapterInterface, which is unsuitable because
    // it lacks commonly used methods like query(), which are only defined in
    // the Adapter class itself.
    public function __invoke(): Adapter
    {
        $config = $this->appConfig->database;
        $config['options']['buffer_results'] = true;
        if (str_contains($config['driver'], 'mysql')) {
            $config['charset'] = 'utf8mb4';
        } else {
            $config['charset'] = 'utf8';
        }

        return new Adapter($config);
    }
}
