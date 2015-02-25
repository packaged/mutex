<?php
namespace Packaged\Mutex\Tests;

use Packaged\Mutex\Mutex;
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
    $timeoutMutex2 = Mutex::create($provider2, 'TimeoutLock')->tryLock(3000);
    $this->assertFalse($timeoutMutex1->isLocked());
    $this->assertTrue($timeoutMutex2->isLocked());

    $this->setExpectedException(
      'Packaged\Mutex\Exceptions\LockFailedException',
      'Failed to lock'
    );
    $timeoutMutex1->tryLock(1000);
  }

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
}
