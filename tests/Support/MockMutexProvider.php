<?php
namespace Packaged\Mutex\Tests\Support;

use Packaged\Mutex\Exceptions\LockFailedException;
use Packaged\Mutex\Providers\AbstractMutexProvider;

class MockMutexProvider extends AbstractMutexProvider
{
  private static $_locks = [];

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

  public function unlock($mutexKey)
  {
    if($this->isLocked($mutexKey))
    {
      unset(self::$_locks[$mutexKey]);
    }
  }

  public function isLocked($mutexKey)
  {
    return isset(self::$_locks[$mutexKey])
    && self::$_locks[$mutexKey] == $this->_getLockId();
  }

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

  public function lockedBy($mutexKey)
  {
    return isset(self::$_locks[$mutexKey]) ? self::$_locks[$mutexKey] : null;
  }
}
