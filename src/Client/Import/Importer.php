<?php

namespace Braintacle\Client\Import;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Model\Config;
use Psr\Http\Message\StreamInterface;

/**
 * Import client from XML file (compressed or uncompressed).
 */
final class Importer
{
    public function __construct(private Config $config, private HttpClient $httpClient) {}

    public function importStream(StreamInterface $stream): void
    {
        $uri = $this->config->communicationServerUri;
        try {
            $this->httpClient->post($uri, [
                'headers' => [
                    // Substring 'local' required for correct server operation
                    'User-Agent' => 'Braintacle_local_upload',
                    'Content-Type' => 'application/x-compress',
                ],
                'body' => $stream,
            ]);
        } catch (GuzzleException $exception) {
            throw new ImportError($exception->getMessage());
        }
    }

    public function importFile(string $fileName): void
    {
        $stream = Utils::streamFor(Utils::tryFopen($fileName, 'r'));
        $this->importStream($stream);
    }
}
