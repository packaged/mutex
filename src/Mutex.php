<?php
namespace Packaged\Mutex;

use Packaged\Mutex\Exceptions\LockFailedException;
use Packaged\Mutex\Interfaces\IMutexProvider;

class Mutex
{
  /**
   * By default a mutex will only be locked for this long (in seconds)
   * NOTE: This should be a reasonable amount less than PHP's max execution time
   */
  const DEFAULT_EXPIRY = 15;

  private $_provider;
  private $_mutexKey;
  private $_unlockOnDestruct;

  /**
   * @param IMutexProvider $provider  Cache Pool to store locks
   * @param string         $mutexName The name of the mutex
   *
   * @throws \Exception
   */
  public function __construct(IMutexProvider $provider, $mutexName)
  {
    if(!$this->isValidKey($mutexName))
    {
      throw new \Exception('Invalid mutex key');
    }

    $this->_provider = $provider;
    $this->_mutexKey = static::createKey($mutexName);
    $this->_unlockOnDestruct = true;
  }

  public static function createKey($mutexName)
  {
    return 'PACKAGED_MUTEX:' . $mutexName;
  }

  /**
   * @param mixed $key
   *
   * @return bool
   *
   * Keys can be up to 250 chars but this package appends an extra string
   */
  public function isValidKey($key)
  {
    return ($key || $key === 0 || $key === "0")
      && (strlen($key) <= 200)
      && (!preg_match('/\s/', $key));
  }

  public static function create(IMutexProvider $provider, $mutexName)
  {
    $object = new static($provider, $mutexName);
    return $object;
  }

  public function __destruct()
  {
    if($this->_unlockOnDestruct)
    {
      $this->unlock();
    }
  }

  public function setUnlockOnDestruct($unlock)
  {
    $this->_unlockOnDestruct = $unlock;
  }

  /**
   * @return bool
   */
  public function isLocked()
  {
    return $this->_provider->isLocked($this->_mutexKey);
  }

  /**
   * Lock the mutex
   *
   * @param int $expiry  How long in seconds to keep the mutex locked just in
   *                     case the script dies. 0 = never expires.
   *
   * @return $this
   * @throws LockFailedException
   */
  public function lock($expiry = self::DEFAULT_EXPIRY)
  {
    $this->_provider->lock($this->_mutexKey, $expiry);
    return $this;
  }

  /**
   * Try to lock the mutex without throwing an exception
   *
   * @param int $expiry    How long in seconds to keep the mutex locked just in
   *                       case the script dies. 0 = never expires.
   *
   * @return bool true if the mutex was locked successfully, false if locking failed
   */
  public function tryLock($expiry = self::DEFAULT_EXPIRY)
  {
    try
    {
      $this->lock($expiry);
      return true;
    }
    catch(LockFailedException $e)
    {
      return false;
    }
  }

  /**
   * Try to lock the mutex
   *
   * @param int $timeout  How long to wait in milliseconds.
   *                      -1 means wait forever
   *                      0 means don't wait at all
   * @param int $expiry   How long in seconds to keep the mutex locked just in
   *                      case the script dies. 0 = never expires.
   * @param int $maxSleep Maximum time to sleep time in milliseconds
   *
   * @return $this
   * @throws LockFailedException
   */
  public function waitLock(
    $timeout, $expiry = self::DEFAULT_EXPIRY, $maxSleep = 1000
  )
  {
    $start = microtime(true);
    $uSleepTime = min(max(500, $timeout), $maxSleep) * 1000;
    while(!$this->isLocked())
    {
      try
      {
        $this->lock($expiry);
        break;
      }
      catch(LockFailedException $e)
      {
        if($timeout == 0 || ((microtime(true) - $start) * 1000) >= $timeout)
        {
          throw $e;
        }
        usleep($uSleepTime);
      }
    }
    return $this;
  }

  /**
   * Unlock the mutex if it is locked
   *
   * @return $this
   */
  public function unlock()
  {
    if($this->isLocked())
    {
      $this->_provider->unlock($this->_mutexKey);
    }
    return $this;
  }

  /**
   * Touch the mutex to reset its expiry if we have it locked.
   * *** NOTICE: This method is not atomic!!! ***
   * If the entry expires between the get and set it could cause a race condition
   *
   * @param int $expiry How long in the future to set the new expiry
   */
  public function touch($expiry = self::DEFAULT_EXPIRY)
  {
    if($this->isLocked())
    {
      $this->_provider->touch($this->_mutexKey, $expiry);
    }
  }

  /**
   * Get the ID of the process currently locking the Mutex
   *
   * @return string|null The process ID or null if it is not locked
   */
  public function lockedBy()
  {
    return $this->_provider->lockedBy($this->_mutexKey);
  }

  /**
   * Call a callable with locking
   *
   * @param IMutexProvider $provider
   * @param                $mutexName
   * @param callable       $callable
   *
   * @param int            $lockTime
   *
   * @return mixed
   * @throws \Exception
   */
  public static function with(IMutexProvider $provider, $mutexName, callable $callable, $lockTime = self::DEFAULT_EXPIRY
  )
  {
    $mutex = new static($provider, $mutexName);
    $mutex->lock($lockTime);
    try
    {
      return $callable($mutex);
    }
    finally
    {
      $mutex->unlock();
    }
  }
}
