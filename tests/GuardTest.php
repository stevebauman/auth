<?php namespace Orchestra\Auth\Tests;

use Mockery as m;
use Orchestra\Auth\Guard;

class GuardTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Provider instance.
     *
     * @var Illuminate\Auth\UserProviderInterface
     */
    private $provider = null;

    /**
     * Session instance.
     *
     * @var Illuminate\Session\Store
     */
    private $session = null;

    /**
     * Event dispatcher instance.
     *
     * @var Illuminate\Event\Dispatcher
     */
    private $events = null;

    /**
     * Setup the test environment
     */
    public function setUp()
    {
        $this->provider = m::mock('\Illuminate\Auth\UserProviderInterface');
        $this->session  = m::mock('\Illuminate\Session\Store');
        $this->events   = m::mock('\Illuminate\Events\Dispatcher');
    }

    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        unset($this->provider);
        unset($this->session);
        unset($this->events);

        m::close();
    }

    /**
     * Test Orchestra\Auth\Guard::roles() method returning valid roles.
     *
     * @test
     */
    public function testRolesMethod()
    {
        $events = $this->events;
        $user   = m::mock('\Illuminate\Auth\UserInterface');
        $user->id = 1;

        $events->shouldReceive('until')
                ->with('orchestra.auth: roles', m::any())->once()
                ->andReturn(array('admin', 'editor'));

        $stub = new Guard($this->provider, $this->session);
        $stub->setDispatcher($events);
        $stub->setUser($user);

        $expected = array('admin', 'editor');
        $output   = $stub->roles();

        $this->assertEquals($expected, $output);
    }

    /**
     * Test Orchestra\Support\Auth::is() method returning valid roles.
     *
     * @test
     */
    public function testIsMethod()
    {
        $events = $this->events;
        $user   = m::mock('\Illuminate\Auth\UserInterface');
        $user->id = 1;

        $events->shouldReceive('until')
                ->with('orchestra.auth: roles', m::any())->once()
                ->andReturn(array('admin', 'editor'));

        $stub = new Guard($this->provider, $this->session);
        $stub->setDispatcher($events);
        $stub->setUser($user);

        $this->assertTrue($stub->is('admin'));
        $this->assertTrue($stub->is('editor'));
        $this->assertFalse($stub->is('user'));

        $this->assertTrue($stub->is(array('admin', 'editor')));
        $this->assertFalse($stub->is(array('admin', 'user')));
    }

    /**
     * Test Orchestra\Support\Auth::logout() method.
     *
     * @test
     */
    public function testLogoutMethod()
    {
        $events  = $this->events;
        $session = $this->session;
        $cookie  = m::mock('\Illuminate\Cookie\CookieJar');

        $events->shouldReceive('until')
                ->with('orchestra.auth: roles', m::any())->never()->andReturn(array('admin', 'editor'))
            ->shouldReceive('fire')
                ->with('auth.logout', m::any())->once()->andReturn(array('admin', 'editor'));
        $session->shouldReceive('forget')->once()->andReturn(null);

        $stub = new Guard($this->provider, $this->session);
        $stub->setDispatcher($events);
        $stub->setCookieJar($cookie);
        $cookie->shouldReceive('queue')->once()->andReturn($cookie)
            ->shouldReceive('forget')->once()->andReturn(null);

        $refl      = new \ReflectionObject($stub);
        $user      = $refl->getProperty('user');
        $userRoles = $refl->getProperty('userRoles');

        $user->setAccessible(true);
        $userRoles->setAccessible(true);

        $user->setValue($stub, (object) array('id' => 1));
        $userRoles->setValue($stub, array(1 => array('admin', 'editor')));

        $this->assertEquals(array('admin', 'editor'), $stub->roles());

        $stub->logout();

        $this->assertNull($userRoles->getValue($stub));
    }
}
