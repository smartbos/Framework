<?php
namespace Wandu\Router;

use Psr\Http\Message\ServerRequestInterface;
use Wandu\Router\Contracts\ClassLoaderInterface;
use Wandu\Router\Exception\HandlerNotFoundException;

class Route
{
    /** @var string */
    protected $className;

    /** @var string */
    protected $methodName;

    /** @var array */
    protected $middlewares;

    /**
     * @param string $className
     * @param string $methodName
     * @param array $middlewares
     */
    public function __construct($className, $methodName, array $middlewares = [])
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->middlewares = $middlewares;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Wandu\Router\Contracts\ClassLoaderInterface $loader
     * @return mixed
     */
    public function execute(ServerRequestInterface $request, ClassLoaderInterface $loader)
    {
        return $this->dispatch($request, $loader, $this->middlewares);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Wandu\Router\Contracts\ClassLoaderInterface $loader
     * @param array $middlewares
     * @return mixed
     */
    protected function dispatch(ServerRequestInterface $request, ClassLoaderInterface $loader, array $middlewares)
    {
        if (count($middlewares)) {
            $middleware = array_shift($middlewares);
            return call_user_func([
                $loader->create($request, $middleware), 'handle'
            ], $request, function (ServerRequestInterface $request) use ($loader, $middlewares) {
                return $this->dispatch($request, $loader, $middlewares);
            });
        }
        $controllerClass = $loader->create($request, $this->className);
        return $loader->call($request, $controllerClass, $this->methodName);
    }
}
