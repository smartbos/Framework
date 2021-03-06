<?php
namespace Wandu\Database;

use PHPUnit_Framework_TestCase;
use Wandu\Database\Connector\MysqlConnector;
use Wandu\Database\Contracts\ConnectionInterface;
use Wandu\Database\Exception\DriverNotFoundException;

class ManagerTest extends PHPUnit_Framework_TestCase
{
    public function testConnectFail()
    {
        $manager = new Manager();
        try {
            $manager->connect([
                'username' => 'root',
                'password' => '',
                'database' => 'sakila',
            ]);
            static::fail();
        } catch (DriverNotFoundException $e) {
            static::assertEquals(DriverNotFoundException::CODE_UNDEFINED, $e->getCode());
            static::assertEquals('driver is not defined.', $e->getMessage());
        }

        try {
            $manager->connect([
                'driver' => 'wrong_driver',
                'username' => 'root',
                'password' => '',
                'database' => 'sakila',
            ]);
            static::fail();
        } catch (DriverNotFoundException $e) {
            static::assertEquals(DriverNotFoundException::CODE_UNSUPPORTED, $e->getCode());
            static::assertEquals("\"wrong_driver\" is not supported.", $e->getMessage());
        }
    }

    public function testConnectSuccess()
    {
        $manager = new Manager();
        
        $connection = $manager->connect([
            'driver' => 'mysql',
            'username' => 'root',
            'password' => '',
            'database' => 'sakila',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => 'local_',
            'timezone' => '+09:00',
        ]);
        static::assertInstanceOf(ConnectionInterface::class, $connection);

        $connection = $manager->connect(new MysqlConnector([
            'username' => 'root',
            'password' => '',
            'database' => 'sakila',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => 'local_',
            'timezone' => '+09:00',
        ]));
        static::assertInstanceOf(ConnectionInterface::class, $connection);
    }
}
