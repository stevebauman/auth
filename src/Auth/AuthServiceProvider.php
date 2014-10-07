<?php namespace Orchestra\Auth;

use Orchestra\Auth\Acl\Factory;
use Illuminate\Auth\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerAuthenticator();

        $this->registerUserResolver();

        $this->registerRequestRebindHandler();

        $this->registerAuthorizator();
    }

    /**
     * Register the service provider for Auth.
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        $this->app->bindShared('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app['auth.loaded'] = true;

            return new AuthManager($app);
        });

        $this->app->bindShared('auth.driver', function ($app) {
            return $app['auth']->driver();
        });
    }

    /**
     * Register the service provider for Acl.
     *
     * @return void
     */
    protected function registerAuthorizator()
    {
        $this->app->bindShared('orchestra.acl', function ($app) {
            return new Factory($app['auth']->driver());
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../');

        $this->package('orchestra/auth', 'orchestra/auth', $path);

        parent::boot();
    }
}
