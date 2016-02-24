<?php

namespace IoCContainer;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class Container
 * @package IoCContainer
 */
class Container
{
    /**
     * This property contained array of closures
     * with raw data to create new objects (services),
     * and information about whether the service exist
     * in a single instance or not.
     * Example: [
     *      'ServiceAlias' => [
     *          'service' => Closure Object,
     *          'factory' => factory flag(true/false)
     *      ]
     * ]
     *
     * @var array
     */
    protected $storage = [];

    /**
     * When service was created and in its declaration
     * factory flag !== true, service will be placed
     * in this array.
     * Example: [
     *      'serviceAlias' => 'serviceInstance'
     * ]
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Contains parameters that can be used when service
     * will be created.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Register new service.
     * This method takes three arguments:
     * 'alias' - service alias, which you specify, when you create
     * new object (service);
     * 'callable' - closure, in which you can set properties for
     * the future service and specify the particular object that
     * you want to create;
     * 'factory' - by default this parameter is false, it means if
     * you try to create the service, that already was created, this
     * method throw RuntimeException. But if you want every time
     * create a new object (service), set this parameter in true.
     *
     * @param string $alias         Service alias.
     * @param Closure $callback     Closure.
     * @param bool $factory         Factory flag.
     * @return void
     * @throws InvalidArgumentException if given alias not string or
     * given factory argument not boolean.
     * @throws RuntimeException if service was already been registered.
     */
    public function register($alias, Closure $callback, $factory = false)
    {
        $this->notString($alias, 'Service');

        if (! is_bool($factory)) {
            throw new InvalidArgumentException('Factory argument must be boolean.');
        }

        if (array_key_exists($alias, $this->storage)) {
            throw new RuntimeException(sprintf('Service named "%s" already exists.', $alias));
        }

        $this->storage[$alias]['service'] = $callback;
        $this->storage[$alias]['factory'] = $factory;
    }

    /**
     * Create new service using the parameters specified in Closure.
     *
     * @param string $alias     Service alias.
     * @return object           Created service.
     * @throws InvalidArgumentException if given alias not string.
     * @throws RuntimeException if service alias not registered or
     * service was already been created and factory flag is false.
     */
    public function make($alias)
    {
        $this->notString($alias, 'Service');

        if (! array_key_exists($alias, $this->storage)) {
            throw new RuntimeException(sprintf('Service "%s" not registered.', $alias));
        }

        if ($this->storage[$alias]['factory'] === true) {
            return call_user_func($this->storage[$alias]['service'], $this);
        }

        if (array_key_exists($alias, $this->instances)) {
            return $this->instances[$alias];
        }

        $service = call_user_func($this->storage[$alias]['service'], $this);
        $this->instances[$alias] = $service;
        return $service;
    }

    /**
     * Determines whether the specified service has been registered.
     *
     * @param string $alias     Service alias.
     * @return bool             If given service has been registered,
     *                          false if no.
     * @throws InvalidArgumentException if given alias not string.
     */
    public function hasService($alias)
    {
        $this->notString($alias, 'Service');

        return (array_key_exists($alias, $this->storage)) ? true : false;
    }

    /**
     * Add a service's parameter.
     *
     * @param string $name      Parameter name.
     * @param mixed $value      Parameter value.
     * @return void
     * @throws InvalidArgumentException if given parameter's name not
     * string.
     * @throws RuntimeException if parameter with the same name already
     * exists.
     */
    public function addParameter($name, $value)
    {
        $this->notString($name, 'Given parameter');

        if (! array_key_exists($name, $this->parameters)) {
            throw new RuntimeException(
                sprintf("Parameter with '%s' name already exists. 
                        If you want update parameter's value, use updateParameter() method.", $name)
            );
        }

        $this->parameters[$name] = $value;
    }

    /**
     * Get parameter's value.
     *
     * @param string $name      Parameter name.
     * @return mixed            Parameter value.
     * @throws InvalidArgumentException if given parameter's name not
     * string.
     * @throws RuntimeException if parameter not found.
     */
    public function getParameter($name)
    {
        $this->notString($name, 'Given parameter');

        $this->notFoundParameter($name);

        return $this->parameters[$name];
    }

    /**
     * Update parameter's value.
     *
     * @param string $name      Parameter name.
     * @param mixed $value      New parameter value.
     * @throws InvalidArgumentException if given parameter's name not
     * string.
     * @throws RuntimeException if parameter not found.
     */
    public function updateParameter($name, $value)
    {
        $this->notString($name, 'Given parameter');

        $this->notFoundParameter($name);

        $this->parameters[$name] = $value;
    }

    /**
     * Remove parameter.
     *
     * @param string $name      Parameter name.
     * @throws InvalidArgumentException if given parameter's name not
     * string.
     */
    public function removeParameter($name)
    {
        $this->notString($name, 'Given parameter');

        if (array_key_exists($name, $this->parameters)) {
            unset($this->parameters[$name]);
        }
    }

    /**
     * Verifies the existence of the required parameter.
     *
     * @param string $name      Parameter name.
     * @return bool             True if parameter exists,
     *                          false if no.
     * @throws InvalidArgumentException if given parameter's name not
     * string.
     */
    public function hasParameter($name)
    {
        $this->notString($name, 'Given parameter');

        return (array_key_exists($name, $this->parameters)) ? true : false;
    }

    /**
     * Checks whether the given alias (or name) is string.
     *
     * @param string $alias     Alias (name).
     * @param string $errMsg    Message, which will be shown on error.
     * @return void
     * @throws InvalidArgumentException if given alias (or name) not
     * string.
     */
    protected function notString($alias, $errMsg)
    {
        if (! is_string($alias)) {
            throw new InvalidArgumentException(sprintf('%s name must be a string.', $errMsg));
        }
    }

    /**
     * Check if required parameter exist in array of parameters.
     *
     * @param string $name  Parameter's name.
     * @throws InvalidArgumentException if given parameter's name
     * not found.
     */
    protected function notFoundParameter($name)
    {
        if (! array_key_exists($name, $this->parameters)) {
            throw new RuntimeException('Required parameter not found.');
        }
    }
}
