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

  private $_mutexKey;

  /**
   * @param IMutexProvider $provider  Cache Pool to store locks
   * @param string         $mutexName The name of the mutex
   */
  public function __construct(IMutexProvider $provider, $mutexName)
  {
    $this->_provider = $provider;
    $this->_mutexKey = 'PACKAGED_MUTEX:' . $mutexName;
  }

  public static function create(IMutexProvider $provider, $mutexName)
  {
    $object = new static($provider, $mutexName);
    return $object;
  }

  public function __destruct()
  {
    $this->unlock();
  }

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
   * @throws LockFailedException
   * @return $this
   */
  public function lock($expiry = self::DEFAULT_EXPIRY)
  {
    $this->_provider->lock($this->_mutexKey, $expiry);
    return $this;
  }

  /**
   * Try to lock the mutex
   *
   * @param int $timeout How long to wait in milliseconds.
   *                     -1 means wait forever
   *                     0 means don't wait at all
   * @param int $expiry  How long in seconds to keep the mutex locked just in
   *                     case the script dies. 0 = never expires.
   *
   * @throws LockFailedException
   * @return $this
   */
  public function tryLock($timeout = 0, $expiry = self::DEFAULT_EXPIRY)
  {
    $start = microtime(true);
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
        usleep(mt_rand(0, min(5000, $timeout)));
      }
    }
    return $this;
  }

  /**
   * Unlock the mutex if it is locked
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
}
