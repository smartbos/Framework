<?php
namespace Wandu\Router;

use FastRoute\Dispatcher as FastDispatcher;
use FastRoute\Dispatcher\GroupCountBased as GCBDispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Wandu\Router\ClassLoader\DefaultLoader;
use Wandu\Router\Contracts\ClassLoaderInterface;
use Wandu\Router\Contracts\ResponsifierInterface;
use Wandu\Router\Contracts\RoutesInterface;
use Wandu\Router\Exception\MethodNotAllowedException;
use Wandu\Router\Exception\RouteNotFoundException;
use Wandu\Router\Responsifier\NullResponsifier;

class Dispatcher
{
    /** @var \Wandu\Router\Contracts\ClassLoaderInterface */
    protected $classLoader;

    /** @var \Wandu\Router\Responsifier\NullResponsifier */
    protected $responsifier;
    
    /** @var \Wandu\Router\Configuration */
    protected $config;
    
    /** @var \Wandu\Router\Contracts\RoutesInterface */
    protected $routes;

    /**
     * @param \Wandu\Router\Contracts\ClassLoaderInterface|null $loader
     * @param \Wandu\Router\Contracts\ResponsifierInterface|null $responsifier
     * @param \Wandu\Router\Configuration|null $config
     */
    public function __construct(
        ClassLoaderInterface $loader = null,
        ResponsifierInterface $responsifier = null,
        Configuration $config = null
    ) {
        $this->classLoader = $loader;
        $this->responsifier = $responsifier;
        $this->config = $config ?: new Configuration([]);
    }

    public function flush()
    {
        if ($this->config->isCacheEnabled()) {
            @unlink($this->config->getCacheFile());
        }
    }

    /**
     * @param \Wandu\Router\Contracts\RoutesInterface $routes
     * @return \Wandu\Router\Dispatcher
     */
    public function withRoutes(RoutesInterface $routes)
    {
        $inst = clone $this;
        $inst->routes = $routes;
        return $inst;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $cacheEnabled = $this->config->isCacheEnabled();

        $cacheFile = $this->config->getCacheFile();
        if ($cacheEnabled && file_exists($cacheFile)) {
            $cacheData = require $cacheFile;
            $dispatchData = $cacheData['dispatch_data'];
            $routes = $cacheData['routes'];
        } else {
            $this->routes->routes($router = new Router);
            $dispatchData = $router->getCollector()->getData();
            $routes = $router->getRoutes();
        }

        if ($cacheEnabled) {
            file_put_contents($cacheFile, '<?php return ' . var_export([
                    'dispatch_data' => $dispatchData,
                    'routes' => $routes,
                ], true) .';');
        }

        $request = $this->applyVirtualMethod($request);
        $routeInfo = $this->runDispatcher(
            new GCBDispatcher($dispatchData),
            $request->getMethod(),
            $request->getUri()->getPath()
        );
        foreach ($routeInfo[2] as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }
        return $routes[$routeInfo[1]]->execute($request, $this->classLoader, $this->responsifier);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function applyVirtualMethod(ServerRequestInterface $request)
    {
        if (!$this->config->isVirtualMethodEnabled()) {
            return $request;
        }
        $parsedBody = $request->getParsedBody();
        if (!isset($parsedBody['_method'])) {
            return $request;
        }
        return $request->withMethod(strtoupper($parsedBody['_method']));
    }

    /**
     * @param \FastRoute\Dispatcher $dispatcher
     * @param string $method
     * @param string $path
     * @return array
     */
    protected function runDispatcher(FastDispatcher $dispatcher, $method, $path)
    {
        $routeInfo = $dispatcher->dispatch($method, $path);
        switch ($routeInfo[0]) {
            case FastDispatcher::NOT_FOUND:
                throw new RouteNotFoundException();
            case FastDispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedException();
            case FastDispatcher::FOUND:
                return $routeInfo;
        }
    }
}
