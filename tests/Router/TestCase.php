<?php
namespace Wandu\Router;

use Mockery;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Wandu\Router\ClassLoader\DefaultLoader;
use Wandu\Router\Responsifier\WanduResponsifier;

class TestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @param string $method
     * @param string $path
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function createRequest($method, $path)
    {
        $mockRequest = Mockery::mock(ServerRequestInterface::class);
        $mockRequest->shouldReceive('getMethod')->andReturn($method);
        $mockRequest->shouldReceive('getUri->getPath')->andReturn($path);
        return $mockRequest;
    }

    /**
     * @param array $config
     * @return \Wandu\Router\Dispatcher
     */
    public function createDispatcher(array $config = [])
    {
        return new Dispatcher(
            new DefaultLoader(),
            new WanduResponsifier(),
            new Configuration($config)
        );
    }
}
