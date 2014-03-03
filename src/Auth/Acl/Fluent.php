<?php namespace Orchestra\Auth\Acl;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Str;

class Fluent
{
    /**
     * Collection name.
     *
     * @var string
     */
    protected $name;

    /**
     * Collection of this instance.
     *
     * @var array
     */
    protected $items = array();

    /**
     * Construct a new instance.
     *
     * @param  string   $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Add a key to collection.
     *
     * @param  string   $key
     * @return boolean
     */
    public function add($key)
    {
        if (is_null($key)) {
            throw new InvalidArgumentException("Can't add NULL {$this->name}.");
        }

        // Typehint the attribute value of an Eloquent result, if it was
        // given instead of a string.
        if ($key instanceof Eloquent) {
            $key = $key->getAttribute('name');
        }

        if ($this->has($key)) {
            return false;
        }

        array_push($this->items, $this->getSlugFromName($key));

        return true;
    }

    /**
     * Add multiple key to collection.
     *
     * @param  array   $keys
     * @return boolean
     */
    public function attach(array $keys)
    {
        foreach ($keys as $key) {
            $this->add($key);
        }

        return true;
    }

    /**
     * Remove multiple key to collection.
     *
     * @param  array   $keys
     * @return boolean
     */
    public function detach(array $keys)
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }

        return true;
    }

    /**
     * Check if an id is set in the collection.
     *
     * @param  integer  $id
     * @return bool
     */
    public function exist($id)
    {
        is_string($id) && $id = $this->getSlugFromName($id);

        return isset($this->items[$id]);
    }

    /**
     * Add multiple key to collection.
     *
     * @deprecated
     * @param  array   $keys
     * @return boolean
     */
    public function fill(array $keys)
    {
        return $this->attach($keys);
    }

    /**
     * Filter request.
     *
     * @param  string|array $request
     * @return array
     */
    public function filter($request)
    {
        if (is_array($request)) {
            return $request;
        } elseif ($request === '*') {
            $request = $this->get();
        } elseif ($request[0] === '!') {
            $request = array_diff($this->get(), array(substr($request, 1)));
        } elseif (! is_array($request)) {
            $request = array($request);
        }

        return $request;
    }

    /**
     * Find collection key from a name.
     *
     * @param  mixed   $name
     * @return integer|null
     */
    public function findKey($name)
    {
        if (! (is_numeric($name) && $this->exist($name))) {
            $name = $this->search($name);
        }

        return $name;
    }

    /**
     * Get the items.
     *
     * @return array
     */
    public function get()
    {
        return $this->items;
    }

    /**
     * Determine whether a key exists in collection.
     *
     * @param  string   $key
     * @return boolean
     */
    public function has($key)
    {
        $key = strval($key);
        $key = $this->getSlugFromName($key);

        return ( ! empty($key) && in_array($key, $this->items));
    }

    /**
     * Remove a key from collection.
     *
     * @param  string   $key
     * @return boolean
     */
    public function remove($key)
    {
        if (is_null($key)) {
            throw new InvalidArgumentException("Can't add NULL {$this->name}.");
        }

        if (! is_null($id = $this->search($key))) {
            unset($this->items[$id]);
            return true;
        }

        return false;
    }

    /**
     * Rename a key from collection.
     *
     * @param  string   $from
     * @param  string   $to
     * @return boolean
     */
    public function rename($from, $to)
    {
        if (is_null($key = $this->search($from))) {
            return false;
        }

        $this->items[$key] = $this->getSlugFromName($to);

        return true;
    }

    /**
     * Get the ID from a key.
     *
     * @param  string   $key
     * @return integer
     */
    public function search($key)
    {
        is_string($key) && $key = $this->getSlugFromName($key);

        $id = array_search($key, $this->items);

        if (false === $id) {
            return null;
        }

        return $id;
    }

    /**
     * Get slug name.
     *
     * @param  string  $name
     */
    protected function getSlugFromName($name)
    {
        return trim(Str::slug($name, '-'));
    }
}