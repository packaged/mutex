<?php
namespace Packaged\Mutex\Tests\Support;

use Packaged\Mutex\Exceptions\LockFailedException;
use Packaged\Mutex\Providers\AbstractMutexProvider;

class MockMutexProvider extends AbstractMutexProvider
{
  private static $_locks = [];

  /**
   * Lock the mutex
   *
   * @param int $mutexKey Identifier of the mutex.
   * @param int $expiry   How long in seconds to keep the mutex locked just in
   *                      case the script dies. 0 = never expires.
   *
   * @throws LockFailedException
   */
  public function lock($mutexKey, $expiry)
  {
    if(isset(self::$_locks[$mutexKey]))
    {
      if(!$this->isLocked($mutexKey))
      {
        throw new LockFailedException();
      }
      return;
    }
    self::$_locks[$mutexKey] = $this->_getLockId();
  }

  /**
   * Unlock the mutex if it is locked
   *
   * @param int $mutexKey Identifier of the mutex.
   */
  public function unlock($mutexKey)
  {
    if($this->isLocked($mutexKey))
    {
      unset(self::$_locks[$mutexKey]);
    }
  }

  /**
   * @param int $mutexKey Identifier of the mutex.
   *
   * @return bool
   */
  public function isLocked($mutexKey)
  {
    return isset(self::$_locks[$mutexKey])
    && self::$_locks[$mutexKey] == $this->_getLockId();
  }

  /**
   * Touch the mutex to reset its expiry if we have it locked.
   *
   * @param int $mutexKey Identifier of the mutex.
   * @param int $expiry   How long in the future to set the new expiry
   */
  public function touch($mutexKey, $expiry)
  {
    if($this->isLocked($mutexKey))
    {
      self::$_locks[$mutexKey] = $this->_getLockId();
    }
  }

  public function expire($id)
  {
    unset(self::$_locks[$id]);
  }
}
