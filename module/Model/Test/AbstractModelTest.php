<?php

namespace Model\Test;

use Model\AbstractModel;
use PHPUnit\Framework\TestCase;

class AbstractModelTest extends TestCase
{
    public function testSetAsProp()
    {
        $model = $this->getMockForAbstractClass(AbstractModel::class);
        $model->foo = 'bar';
        $this->assertTrue(isset($model->foo));
        $this->assertTrue(isset($model['Foo']));
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals('bar', $model['Foo']);
    }

    public function testSetAsKey()
    {
        $model = $this->getMockForAbstractClass(AbstractModel::class);
        $model['Foo'] = 'bar';
        $this->assertTrue(isset($model->foo));
        $this->assertTrue(isset($model['Foo']));
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals('bar', $model['Foo']);
    }

    public function testSetDefinedPropertyAsProp()
    {
        $model = new class () extends AbstractModel {
            public $foo;
        };
        $model->foo = 'bar';
        $this->assertTrue(isset($model->foo));
        $this->assertTrue(isset($model['Foo']));
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals('bar', $model['Foo']);
    }

    public function testSetDefinedPropertyAsKey()
    {
        $model = new class () extends AbstractModel {
            public $foo;
        };
        $model['Foo'] = 'bar';
        $this->assertTrue(isset($model->foo));
        $this->assertTrue(isset($model['Foo']));
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals('bar', $model['Foo']);
    }

    public function testGetArrayCopy()
    {
        $model = $this->getMockForAbstractClass(AbstractModel::class);
        $model->foo = 'bar';
        $model['Bar'] = 'baz';
        $this->assertEquals(['Foo' => 'bar', 'Bar' => 'baz'], $model->getArrayCopy());
    }

    public function testExchangeArray()
    {
        $model = $this->getMockForAbstractClass(AbstractModel::class);
        $model->exchangeArray(['Foo' => 'bar', 'Bar' => 'baz']);
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals('baz', $model['Bar']);
    }
}
