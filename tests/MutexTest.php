<?php
namespace Packaged\Mutex\Tests;

use Packaged\Mutex\Mutex;
use Packaged\Mutex\Providers\MemcachedMutexProvider;
use Packaged\Mutex\Providers\MemcacheMutexProvider;
use Packaged\Mutex\Tests\Support\MockMutexProvider;

class MutexTest extends \PHPUnit_Framework_TestCase
{
  public function testMutex()
  {
    $provider = new MockMutexProvider();
    $mutex1 = Mutex::create($provider, 'TestMutex')->lock();
    $mutex1->lock();
    $this->assertTrue($mutex1->isLocked());
    $mutex2 = Mutex::create($provider, 'TestMutex2')->lock();
    $this->assertTrue($mutex1->isLocked());
    $mutex2->touch();
    $this->assertTrue($mutex2->isLocked());
    $provider->expire('PACKAGED_MUTEX:TestMutex2');
    $this->assertFalse($mutex2->isLocked());
    $mutex2->touch();
    $this->assertFalse($mutex2->isLocked());

    $mutex2->lock();
  }

  public function testMutexLocked()
  {
    $this->setExpectedException(
      'Packaged\Mutex\Exceptions\LockFailedException',
      'Failed to lock'
    );
    $provider1 = new MockMutexProvider();
    $provider2 = new MockMutexProvider();
    $mutex1 = Mutex::create($provider1, 'CacheLock')->lock();
    $mutex2 = Mutex::create($provider2, 'CacheLock')->lock();

    $this->assertTrue($mutex1->isLocked());
    $this->assertFalse($mutex2->isLocked());
  }

  /**
   * @requires extension memcache
   */
  public function testMemcache()
  {
    $memcache = new \Memcache();
    $memcache->addserver('127.0.0.1');
    $provider = new MemcacheMutexProvider($memcache);
    $mutex1 = Mutex::create($provider, 'TestMutex')->lock();
    $mutex1->lock();
    $this->assertTrue($mutex1->isLocked());
    $mutex2 = Mutex::create($provider, 'TestMutex2')->lock();
    $this->assertTrue($mutex1->isLocked());
    $mutex2->touch(1);
    $this->assertTrue($mutex2->isLocked());
    sleep(2);
    $this->assertFalse($mutex2->isLocked());
    $mutex2->touch();
    $this->assertFalse($mutex2->isLocked());
    $mutex2->lock(2);

    $provider2 = new MemcacheMutexProvider($memcache);
    $timeoutMutex1 = Mutex::create($provider, 'TimeoutLock')->lock(2);
    $this->assertTrue($timeoutMutex1->isLocked());
    $timeoutMutex2 = Mutex::create($provider2, 'TimeoutLock')->waitLock(3000);
    $this->assertFalse($timeoutMutex1->isLocked());
    $this->assertTrue($timeoutMutex2->isLocked());

    $this->setExpectedException(
      'Packaged\Mutex\Exceptions\LockFailedException',
      'Failed to lock'
    );
    $timeoutMutex1->waitLock(1000);
  }

  /**
   * @requires extension memcache
   */
  public function testMemcacheLocked()
  {
    $this->setExpectedException(
      'Packaged\Mutex\Exceptions\LockFailedException',
      'Failed to lock'
    );
    $memcache = new \Memcache();
    $memcache->addserver('127.0.0.1');
    $provider1 = new MemcacheMutexProvider($memcache);
    $provider2 = new MemcacheMutexProvider($memcache);
    $mutex1 = Mutex::create($provider1, 'CacheLock')->lock();
    $mutex2 = Mutex::create($provider2, 'CacheLock')->lock();

    $this->assertTrue($mutex1->isLocked());
    $this->assertFalse($mutex2->isLocked());
  }

  public function testMemcached()
  {
    $memcache = new \Memcached();
    $memcache->addServer('127.0.0.1', 11211);
    $provider = new MemcachedMutexProvider($memcache);
    $mutex1 = Mutex::create($provider, 'dTestMutex')->lock();
    $mutex1->lock();
    $this->assertTrue($mutex1->isLocked());
    $mutex2 = Mutex::create($provider, 'dTestMutex2')->lock();
    $this->assertTrue($mutex1->isLocked());
    $mutex2->touch(1);
    $this->assertTrue($mutex2->isLocked());
    sleep(2);
    $this->assertFalse($mutex2->isLocked());
    $mutex2->touch();
    $this->assertFalse($mutex2->isLocked());
    $mutex2->lock(2);

    $provider2 = new MemcachedMutexProvider($memcache);
    $timeoutMutex1 = Mutex::create($provider, 'dTimeoutLock')->lock(2);
    $this->assertTrue($timeoutMutex1->isLocked());
    $timeoutMutex2 = Mutex::create($provider2, 'dTimeoutLock')->waitLock(3000);
    $this->assertFalse($timeoutMutex1->isLocked());
    $this->assertTrue($timeoutMutex2->isLocked());

    $this->setExpectedException(
      'Packaged\Mutex\Exceptions\LockFailedException',
      'Failed to lock'
    );
    $timeoutMutex1->waitLock(1000);
  }

  public function testMemcachedLocked()
  {
    $this->setExpectedException(
      'Packaged\Mutex\Exceptions\LockFailedException',
      'Failed to lock'
    );
    $memcache = new \Memcached();
    $memcache->addServer('127.0.0.1', 11211);
    $provider1 = new MemcachedMutexProvider($memcache);
    $provider2 = new MemcachedMutexProvider($memcache);
    $mutex1 = Mutex::create($provider1, 'dCacheLock')->lock();
    $mutex2 = Mutex::create($provider2, 'dCacheLock')->lock();

    $this->assertTrue($mutex1->isLocked());
    $this->assertFalse($mutex2->isLocked());
  }

  public function testMemcachedTryLock()
  {
    $memcache = new \Memcached();
    $memcache->addServer('127.0.0.1', 11211);
    $provider1 = new MemcachedMutexProvider($memcache);
    $provider2 = new MemcachedMutexProvider($memcache);
    $mutex1 = Mutex::create($provider1, 'dCacheLock');
    $mutex2 = Mutex::create($provider2, 'dCacheLock');

    $this->assertTrue($mutex1->tryLock());
    $this->assertFalse($mutex2->tryLock());

    $this->assertTrue($mutex1->isLocked());
    $this->assertFalse($mutex2->isLocked());
  }

  public function testKeyValidationPass()
  {
    $provider = new MockMutexProvider();
    new Mutex($provider, str_repeat("x", 200));

    $provider = new MockMutexProvider();
    new Mutex($provider, 0);

    $provider = new MockMutexProvider();
    new Mutex($provider, '0');
  }

  /** @expectedException \Exception */
  public function testKeyValidationNullException()
  {
    $provider = new MockMutexProvider();
    new Mutex($provider, null);
  }

  /** @expectedException \Exception */
  public function testKeyValidationSpaceException()
  {
    $provider = new MockMutexProvider();
    new Mutex($provider, 'xxx xxx');
  }

  /** @expectedException \Exception */
  public function testKeyValidationTabException()
  {
    $provider = new MockMutexProvider();
    new Mutex($provider, "xxx\txxx");
  }

  /** @expectedException \Exception */
  public function testKeyValidationNewLineException()
  {
    $provider = new MockMutexProvider();
    new Mutex($provider, "xxx\nxxx");
  }

  /** @expectedException \Exception */
  public function testKeyValidationCarriageReturnException()
  {
    $provider = new MockMutexProvider();
    new Mutex($provider, "xxx\rxxx");
  }

  /** @expectedException \Exception */
  public function testKeyValidationLongException()
  {
    $provider = new MockMutexProvider();
    new Mutex($provider, str_repeat("x", 201));
  }

  /** @throws \Exception */
  public function testWithMutex()
  {
    $provider = new MockMutexProvider();
    $result = Mutex::with($provider, 'withlock', function (Mutex $mutext) { return $mutext->isLocked(); });
    $this->assertTrue($result);

    $this->assertFalse(Mutex::create($provider, 'withlock')->isLocked());
  }
}
