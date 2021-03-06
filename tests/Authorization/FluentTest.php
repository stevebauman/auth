<?php namespace Orchestra\Authorization\TestCase;

use Mockery as m;
use Illuminate\Support\Collection;
use Orchestra\Authorization\Fluent;

class FluentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Stub instance.
     *
     * @return Orchestra\Authorization\Fluent
     */
    private $stub = null;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $this->stub = new Fluent('stub');
        $this->stub->attach(['Hello World']);
    }

    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        unset($this->stub);
        m::close();
    }

    /**
     * Test instanceof stub.
     *
     * @test
     */
    public function testInstanceOf()
    {
        $this->assertInstanceOf('\Orchestra\Authorization\Fluent', $this->stub);

        $refl = new \ReflectionObject($this->stub);
        $name = $refl->getProperty('name');
        $name->setAccessible(true);

        $this->assertEquals('stub', $name->getValue($this->stub));
    }

    /**
     * Test Orchestra\Authorization\Fluent::add() method.
     *
     * @test
     */
    public function testAddMethod()
    {
        $stub = new Fluent('foo');
        $model = m::mock('\Illuminate\Database\Eloquent\Model');

        $model->shouldReceive('getAttribute')->once()->with('name')->andReturn('eloquent');

        $stub->add('foo');
        $stub->add('foobar');
        $stub->add($model);

        $refl = new \ReflectionObject($stub);
        $items = $refl->getProperty('items');
        $items->setAccessible(true);

        $expected = ['foo', 'foobar', 'eloquent'];
        $this->assertEquals($expected, $items->getValue($stub));
        $this->assertEquals($expected, $stub->get());
    }

    /**
     * Test Orchestra\Authorization\Fluent::add() method null throw an exception.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testAddMethodNullThrownException()
    {
        $stub = new Fluent('foo');

        $stub->add(null);
    }

    /**
     * Test Orchestra\Authorization\Fluent::attach() method.
     *
     * @test
     */
    public function testAttachMethod()
    {
        $stub = new Fluent('foo');

        $stub->attach(['foo', 'foobar']);

        $refl = new \ReflectionObject($stub);
        $items = $refl->getProperty('items');
        $items->setAccessible(true);

        $this->assertEquals(['foo', 'foobar'], $items->getValue($stub));
        $this->assertEquals(['foo', 'foobar'], $stub->get());
    }

    /**
     * Test Orchestra\Authorization\Fluent::attach() method.
     *
     * @test
     */
    public function testAttachMethodGivenArrayable()
    {
        $stub = new Fluent('foo');

        $stub->attach(new Collection(['foo', 'foobar']));

        $refl = new \ReflectionObject($stub);
        $items = $refl->getProperty('items');
        $items->setAccessible(true);

        $this->assertEquals(['foo', 'foobar'], $items->getValue($stub));
        $this->assertEquals(['foo', 'foobar'], $stub->get());
    }

    /**
     * Test Orchestra\Authorization\Fluent::attach() method null throw an exception.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testAttachMethodNullThrownException()
    {
        $stub = new Fluent('foo');

        $stub->attach([null]);
    }

    /**
     * Test Orchestra\Authorization\Fluent::has() method.
     *
     * @test
     */
    public function testHasMethod()
    {
        $this->assertTrue($this->stub->has('hello-world'));
        $this->assertFalse($this->stub->has('goodbye-world'));
    }

    /**
     * Test Orchestra\Authorization\Fluent::rename() method.
     *
     * @test
     */
    public function testRenameMethod()
    {
        $stub = new Fluent('foo');

        $stub->attach(['foo', 'foobar']);

        $stub->rename('foo', 'laravel');

        $refl = new \ReflectionObject($stub);
        $items = $refl->getProperty('items');
        $items->setAccessible(true);

        $this->assertEquals(['laravel', 'foobar'], $items->getValue($stub));
        $this->assertEquals(['laravel', 'foobar'], $stub->get());

        $this->assertFalse($stub->rename('foo', 'hello'));
    }

    /**
     * Test Orchestra\Authorization\Fluent::search() method.
     *
     * @test
     */
    public function testSearchMethod()
    {
        $stub = new Fluent('foo');

        $stub->attach(['foo', 'foobar']);

        $this->assertEquals(0, $stub->search('foo'));
        $this->assertEquals(1, $stub->search('foobar'));
        $this->assertNull($stub->search('laravel'));
    }

    /**
     * Test Orchestra\Authorization\Fluent::exists() method.
     *
     * @test
     */
    public function testExistsMethod()
    {
        $stub = new Fluent('foo');

        $stub->attach(['foo', 'foobar']);

        $this->assertTrue($stub->exists(0));
        $this->assertTrue($stub->exists(1));
        $this->assertFalse($stub->exists(3));
    }

    /**
     * Test Orchestra\Authorization\Fluent::remove() method.
     *
     * @test
     */
    public function testRemoveMethod()
    {
        $stub = new Fluent('foo');

        $stub->attach(['foo', 'foobar']);

        $this->assertEquals(['foo', 'foobar'], $stub->get());

        $stub->remove('foo');

        $this->assertFalse($stub->exists(0));
        $this->assertTrue($stub->exists(1));
        $this->assertEquals([1 => 'foobar'], $stub->get());

        $stub->attach(['foo']);

        $this->assertEquals([1 => 'foobar', 2 => 'foo'], $stub->get());

        $stub->remove('foo');

        $this->assertFalse($stub->exists(0));
        $this->assertTrue($stub->exists(1));
        $this->assertFalse($stub->exists(2));
        $this->assertEquals([1 => 'foobar'], $stub->get());

        $this->assertFalse($stub->remove('hello'));
    }

    /**
     * Test Orchestra\Authorization\Fluent::detach() method.
     *
     * @test
     */
    public function testDetachMethod()
    {
        $stub = new Fluent('foo');

        $stub->attach(['foo', 'foobar']);

        $this->assertEquals(['foo', 'foobar'], $stub->get());

        $stub->detach(['foo']);

        $this->assertFalse($stub->exists(0));
        $this->assertTrue($stub->exists(1));
        $this->assertEquals([1 => 'foobar'], $stub->get());

        $stub->attach(['foo']);

        $this->assertEquals([1 => 'foobar', 2 => 'foo'], $stub->get());

        $stub->detach(['foo']);

        $this->assertFalse($stub->exists(0));
        $this->assertTrue($stub->exists(1));
        $this->assertFalse($stub->exists(2));
        $this->assertEquals([1 => 'foobar'], $stub->get());
    }

    /**
     * Test Orchestra\Authorization\Fluent::detach() method.
     *
     * @test
     */
    public function testDetachMethodGivenArrayable()
    {
        $stub = new Fluent('foo');

        $stub->attach(['foo', 'foobar']);

        $this->assertEquals(['foo', 'foobar'], $stub->get());

        $stub->detach(new Collection(['foo']));

        $this->assertFalse($stub->exists(0));
        $this->assertTrue($stub->exists(1));
        $this->assertEquals([1 => 'foobar'], $stub->get());

        $stub->attach(['foo']);

        $this->assertEquals([1 => 'foobar', 2 => 'foo'], $stub->get());

        $stub->detach(new Collection(['foo']));

        $this->assertFalse($stub->exists(0));
        $this->assertTrue($stub->exists(1));
        $this->assertFalse($stub->exists(2));
        $this->assertEquals([1 => 'foobar'], $stub->get());
    }

    /**
     * Test Orchestra\Authorization\Fluent::remove() method null throw an exception.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveMethodNullThrownException()
    {
        with(new Fluent('foo'))->remove(null);
    }

    /**
     * Test Orchestra\Authorization\Fluent::filter() method.
     *
     * @test
     */
    public function testFilterMethod()
    {
        $stub = new Fluent('foo');
        $stub->attach(['foo', 'foobar']);

        $this->assertEquals(['foo', 'foobar'], $stub->filter('*'));
        $this->assertEquals([1 => 'foobar'], $stub->filter('!foo'));
        $this->assertEquals(['hello-world'], $stub->filter('hello-world'));
    }
}
