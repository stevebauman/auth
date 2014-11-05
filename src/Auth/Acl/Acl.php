<?php namespace Orchestra\Auth\Acl;

use RuntimeException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Orchestra\Contracts\Auth\Guard;
use Orchestra\Memory\ContainerTrait;
use Orchestra\Contracts\Memory\Provider;
use Orchestra\Contracts\Auth\Acl\Acl as AclContract;

class Acl implements AclContract
{
    use AuthorizationTrait, ContainerTrait;

    /**
     * Acl instance name.
     *
     * @var string
     */
    protected $name;

    /**
     * Construct a new object.
     *
     * @param  \Orchestra\Contracts\Auth\Guard  $auth
     * @param  string  $name
     * @param  \Orchestra\Contracts\Memory\Provider|null  $memory
     */
    public function __construct(Guard $auth, $name, Provider $memory = null)
    {
        $this->auth    = $auth;
        $this->name    = $name;
        $this->roles   = new Fluent('roles');
        $this->actions = new Fluent('actions');

        $this->roles->add('guest');
        $this->attach($memory);
    }

    /**
     * Bind current ACL instance with a Memory instance.
     *
     * @param  \Orchestra\Contracts\Memory\Provider  $memory
     * @return void
     * @throws \RuntimeException if $memory has been attached.
     */
    public function attach(Provider $memory = null)
    {
        if ($this->attached() && $memory !== $this->memory) {
            throw new RuntimeException(
                "Unable to assign multiple Orchestra\Memory instance."
            );
        }

        // since we already check instanceof Orchestra\Memory\Provider,
        // It safe to just check for not NULL.
        if (! is_null($memory)) {
            $this->setMemoryProvider($memory);
            $this->initiate();
        }
    }

    /**
     * Initiate acl data from memory.
     *
     * @return $this
     */
    protected function initiate()
    {
        $name = $this->name;
        $data = array('acl' => array(), 'actions' => array(), 'roles' => array());
        $data = array_merge($data, $this->memory->get("acl_{$name}", array()));

        // Loop through all the roles and actions in memory and add it to
        // this ACL instance.
        $this->roles->attach($data['roles']);
        $this->actions->attach($data['actions']);

        // Loop through all the acl in memory and add it to this ACL
        // instance.
        foreach ($data['acl'] as $id => $allow) {
            list($role, $action) = explode(':', $id);
            $this->assign($role, $action, $allow);
        }

        return $this->sync();
    }

    /**
     * Assign single or multiple $roles + $actions to have access.
     *
     * @param  string|array    $roles      A string or an array of roles
     * @param  string|array    $actions    A string or an array of action name
     * @param  bool            $allow
     * @return $this
     */
    public function allow($roles, $actions, $allow = true)
    {
        $this->setAuthorization($roles, $actions, $allow);

        return $this->sync();
    }

    /**
     * Verify whether current user has sufficient roles to access the
     * actions based on available type of access.
     *
     * @param  string  $action     A string of action name
     * @return bool
     */
    public function can($action)
    {
        $roles = $this->getUserRoles();

        return $this->checkAuthorization($roles, $action);
    }

    /**
     * Verify whether given roles has sufficient roles to access the
     * actions based on available type of access.
     *
     * @param  string|array $roles      A string or an array of roles
     * @param  string       $action     A string of action name
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function check($roles, $action)
    {
        return $this->checkAuthorization($roles, $action);
    }

    /**
     * Shorthand function to deny access for single or multiple
     * $roles and $actions.
     *
     * @param  string|array     $roles      A string or an array of roles
     * @param  string|array     $actions    A string or an array of action name
     * @return $this
     */
    public function deny($roles, $actions)
    {
        return $this->allow($roles, $actions, false);
    }

    /**
     * Sync memory with acl instance, make sure anything that added before
     * ->with($memory) got called is appended to memory as well.
     *
     * @return $this
     */
    public function sync()
    {
        if ($this->attached()) {
            $name = $this->name;

            $this->memory->put("acl_{$name}", array(
                "acl"     => $this->acl,
                "actions" => $this->actions->get(),
                "roles"   => $this->roles->get(),
            ));
        }

        return $this;
    }

    /**
     * Forward call to roles or actions.
     *
     * @param  string   $type           'roles' or 'actions'
     * @param  string   $operation
     * @param  array    $parameters
     * @return Fluent
     */
    public function execute($type, $operation, array $parameters = array())
    {
        return call_user_func_array(array($this->{$type}, $operation), $parameters);
    }

    /**
     * Magic method to mimic roles and actions manipulation.
     *
     * @param  string   $method
     * @param  array    $parameters
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        list($type, $operation) = $this->resolveDynamicExecution($method);

        $response = $this->execute($type, $operation, $parameters);

        if ($operation === 'has') {
            return $response;
        }

        return $this->sync();
    }

    /**
     * Dynamically resolve operation name especially to resolve attach and
     * detach multiple actions or roles.
     *
     * @param  string  $method
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function resolveDynamicExecution($method)
    {
        // Preserve legacy CRUD structure for actions and roles.
        $method  = Str::snake($method, '_');
        $matcher = '/^(add|rename|has|get|remove|fill|attach|detach)_(role|action)(s?)$/';

        if (! preg_match($matcher, $method, $matches)) {
            throw new InvalidArgumentException("Invalid keyword [$method]");
        }

        $type      = $matches[2].'s';
        $multiple  = (isset($matches[3]) && $matches[3] === 's');
        $operation = $this->resolveOperationName($matches[1], $multiple);

        return array($type, $operation);
    }

    /**
     * Dynamically resolve operation name especially when multiple
     * operation was used.
     *
     * @param  string   $operation
     * @param  bool     $multiple
     * @return string
     */
    protected function resolveOperationName($operation, $multiple = true)
    {
        if (! $multiple) {
            return $operation;
        } elseif (in_array($operation, array('fill', 'add'))) {
            return 'attach';
        }

        return 'detach';
    }
}
