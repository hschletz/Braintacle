<?php

namespace Braintacle\Test\Template\Function;

use Braintacle\Template\Function\AssetUrlFunction;
use PHPUnit\Framework\TestCase;

class AssetUrlFunctionTest extends TestCase
{
    public function testInvoke()
    {
        $assetUrlFunction = new AssetUrlFunction();
        $this->assertMatchesRegularExpression('#^style.css\?\d+$#', $assetUrlFunction('style.css'));
    }
}
