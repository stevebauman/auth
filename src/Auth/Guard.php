<?php namespace Orchestra\Auth;

use Illuminate\Support\Arr;
use Illuminate\Auth\Guard as BaseGuard;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Auth\Authenticatable;
use Orchestra\Contracts\Auth\Guard as GuardContract;

class Guard extends BaseGuard implements GuardContract
{
    /**
     * Cached user to roles relationship.
     *
     * @var array
     */
    protected $userRoles = null;

    /**
     * Setup roles event listener.
     *
     * @param  \Closure|string  $event
     *
     * @return void
     */
    public function setup($event)
    {
        $this->userRoles = null;
        $this->events->forget('orchestra.auth: roles');
        $this->events->listen('orchestra.auth: roles', $event);
    }

    /**
     * Get the current user's roles of the application.
     *
     * If the user is a guest, empty array should be returned.
     *
     * @return array
     */
    public function roles()
    {
        $user   = $this->user();
        $userId = 0;

        // This is a simple check to detect if the user is actually logged-in,
        // otherwise it's just as the same as setting userId as 0.
        is_null($user) || $userId = $user->getAuthIdentifier();

        $roles = Arr::get($this->userRoles, "{$userId}", []);

        // This operation might be called more than once in a request, by
        // cached the event result we can avoid duplicate events being fired.
        if (empty($roles)) {
            $roles = $this->getUserRolesFromEventDispatcher($user, $roles);
        }

        Arr::set($this->userRoles, "{$userId}", $roles);

        return $roles;
    }

    /**
     * Determine if current user has the given role.
     *
     * @param  string|array  $roles
     *
     * @return bool
     */
    public function is($roles)
    {
        $userRoles = $this->roles();

        // For a pre-caution, we should return false in events where user
        // roles not an array.
        if (! is_array($userRoles)) {
            return false;
        }

        // We should ensure that all given roles match the current user,
        // consider it as a AND condition instead of OR.
        foreach ((array) $roles as $role) {
            if (! in_array($role, $userRoles)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if current user has any of the given role.
     *
     * @param  array   $roles
     *
     * @return bool
     */
    public function isAny(array $roles)
    {
        $userRoles = $this->roles();

        // For a pre-caution, we should return false in events where user
        // roles not an array.
        if (! is_array($userRoles)) {
            return false;
        }

        // We should ensure that any given roles match the current user,
        // consider it as OR condition.
        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if current user does not has any of the given role.
     *
     * @param  string   $roles
     *
     * @return bool
     */
    public function isNot($roles)
    {
        return ! $this->is($roles);
    }

    /**
     * Determine if current user does not has any of the given role.
     *
     * @param  array   $roles
     *
     * @return bool
     */
    public function isNotAny(array $roles)
    {
        return ! $this->isAny($roles);
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        parent::logout();

        // We should flush the cached user roles relationship so any
        // subsequent request would re-validate all information,
        // instead of referring to the cached value.
        $this->userRoles = null;
    }

    /**
     * Ger user roles from event dispatcher.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $roles
     *
     * @return array
     */
    protected function getUserRolesFromEventDispatcher(Authenticatable $user = null, $roles = [])
    {
        $roles = $this->events->until('orchestra.auth: roles', [$user, (array) $roles]);

        // It possible that after event are all propagated we don't have a
        // roles for the user, in this case we should properly append "Guest"
        // user role to the current user.
        if (is_null($roles)) {
            return ['Guest'];
        }

        return ($roles instanceof Arrayable ? $roles->toArray() : $roles);
    }
}
