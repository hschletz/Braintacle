<?php

namespace Braintacle\Template\Function;

use Braintacle\Http\RouteHelper;
use Composer\InstalledVersions;

/**
 * Generate URL for static assets in the public/ directory.
 */
class AssetUrlFunction
{
    public function __construct(private RouteHelper $routeHelper)
    {
    }

    public function __invoke(string $path): string
    {
        $fileName = InstalledVersions::getRootPackage()['install_path'] . 'public/' . $path;
        $timestamp = filemtime($fileName);

        return $this->routeHelper->getBasePath() . '/' . $path . '?' . $timestamp;
    }
}
