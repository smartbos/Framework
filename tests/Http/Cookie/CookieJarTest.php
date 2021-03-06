<?php
namespace Wandu\Http\Cookie;

use Mockery;
use Traversable;
use Wandu\Http\Parameters\ParameterTest;

class CookieJarTest extends ParameterTest
{
    /** @var \Wandu\Http\Cookie\CookieJar */
    private $cookies;

    /** @var \Wandu\Http\Contracts\ParameterInterface */
    protected $param1;

    /** @var \Wandu\Http\Contracts\ParameterInterface */
    protected $param2;

    /** @var \Wandu\Http\Contracts\ParameterInterface */
    protected $param3;

    public function setUp()
    {
        $this->param1 = new CookieJar([
            'string' => 'string!',
            'number' => '10',
        ]);
        $this->param2 = new CookieJar([
            'null' => null,
            'empty' => '',
            'false' => false,
        ]);

        $this->param3 = new CookieJar([
            'string1' => 'string 1!',
            'string2' => 'string 2!',
        ], new CookieJar([
            'string1' => 'string 1 fallback!',
            'fallback' => 'fallback!',
        ]));

        $this->cookies = new CookieJar([
            'user' => '0000-1111-2222-3333',
        ]);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testGet()
    {
        static::assertEquals('0000-1111-2222-3333', $this->cookies->get('user'));
        static::assertNull($this->cookies->get('not_exists_key'));
    }

    public function testSetAndRemove()
    {
        // first
        static::assertEquals('0000-1111-2222-3333', $this->cookies->get('user'));
        static::asserttrue($this->cookies->has('user'));

        static::assertNull($this->cookies->get('_new'));
        static::assertFalse($this->cookies->has('_new'));

        // set
        $this->cookies->set('_new', "new value!");
        static::assertEquals('new value!', $this->cookies->get('_new'));
        static::assertTrue($this->cookies->has('_new'));

        $this->cookies->remove('_new');
        static::assertNull($this->cookies->get('_new'));
        static::assertFalse($this->cookies->has('_new'));

        $this->cookies->remove('user');
        static::assertNull($this->cookies->get('user'));
        static::assertFalse($this->cookies->has('user'));
    }

    public function testGetIterator()
    {
        static::assertEquals([], $this->checkCookieAndGetKeys($this->cookies));

        $this->cookies->set('hello', 'world');
        static::assertEquals(['hello'], $this->checkCookieAndGetKeys($this->cookies));

        // remove also added iterate
        $this->cookies->remove('user');
        $this->cookies->remove('unknown');
        static::assertEquals(['hello', 'user', 'unknown'], $this->checkCookieAndGetKeys($this->cookies));
    }

    public function testArrayAccess()
    {
        static::assertSame($this->cookies->get('user'), $this->cookies['user']);
        static::assertSame($this->cookies->get('unknown'), $this->cookies['unknown']);

        static::assertSame($this->cookies->has('user'), isset($this->cookies['user']));
        static::assertSame($this->cookies->has('unknown'), isset($this->cookies['unknown']));

        static::assertFalse($this->cookies->has('added'));
        $this->cookies['added'] = 'added!';
        static::assertTrue($this->cookies->has('added'));

        static::assertTrue($this->cookies->has('user'));
        unset($this->cookies['user']);
        static::assertFalse($this->cookies->has('user'));
    }

    protected function checkCookieAndGetKeys(Traversable $iterator)
    {
        $iterateKeys = [];
        foreach ($iterator as $key => $cookie) {
            $iterateKeys[] = $key;
            static::assertInstanceOf(Cookie::class, $cookie);
        }
        return $iterateKeys;
    }
}
