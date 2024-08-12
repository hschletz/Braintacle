<?php

namespace Braintacle\Test\Http;

use Braintacle\Http\OrderHelper;
use PHPUnit\Framework\TestCase;

class OrderHelperTest extends TestCase
{
    public function testDefaults()
    {
        $this->assertEquals(
            ['Default', 'asc'],
            (new OrderHelper())([], 'Default')
        );
    }

    public function testExplicitOrder()
    {
        $this->assertEquals(
            ['Order', 'asc'],
            (new OrderHelper())(['order' => 'Order'], 'Default')
        );
    }

    public function testEmptyOrder()
    {
        $this->assertEquals(
            ['Default', 'asc'],
            (new OrderHelper())(['order' => ''], 'Default')
        );
    }

    public function testExplicitOrderAndDirection()
    {
        $this->assertEquals(
            ['Order', 'asc'],
            (new OrderHelper())(['order' => 'Order', 'direction' => 'asc'], 'Default')
        );
    }

    public function testExplicitOrderAndNonDefaultDirection()
    {
        $this->assertEquals(
            ['Order', 'desc'],
            (new OrderHelper())(['order' => 'Order', 'direction' => 'desc'], 'Default')
        );
    }

    public function testExplicitOrderAndInvalidDirection()
    {
        $this->assertEquals(
            ['Order', 'asc'],
            (new OrderHelper())(['order' => 'Order', 'direction' => 'invalid'], 'Default')
        );
    }

    public function testInvalidDirectionAndNonstandardDefaultDirection()
    {
        $this->assertEquals(
            ['Order', 'desc'],
            (new OrderHelper())(['order' => 'Order', 'direction' => 'invalid'], 'Default', 'desc')
        );
    }
}
