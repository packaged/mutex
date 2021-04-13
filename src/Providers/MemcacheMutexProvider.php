<?php
namespace Packaged\Mutex\Providers;

use Packaged\Mutex\Exceptions\LockFailedException;

class MemcacheMutexProvider extends AbstractMutexProvider
{
  /**
   * @var \Memcache
   */
  private $_memcache;

  public function __construct(\Memcache $cachePool)
  {
    $this->_memcache = $cachePool;
  }

  public function lock($mutexKey, $expiry)
  {
    // Try and set the value. If it fails check to see if
    // it already contains our ID
    if(!$this->_memcache->add($mutexKey, $this->_getLockId(), null, $expiry))
    {
      if(!$this->isLocked($mutexKey))
      {
        throw new LockFailedException();
      }
    }
    return $this;
  }

  public function unlock($mutexKey)
  {
    if($this->isLocked($mutexKey))
    {
      $this->_memcache->delete($mutexKey);
    }
    return $this;
  }

  /**
   * @param string $mutexKey Identifier of the mutex.
   *
   * @return bool
   */
  public function isLocked($mutexKey)
  {
    return ($this->_memcache->get($mutexKey) == $this->_getLockId());
  }

  /**
   * Touch the mutex to reset its expiry if we have it locked.
   *
   * @param string $mutexKey Identifier of the mutex.
   * @param int    $expiry   How long in the future to set the new expiry
   */
  public function touch($mutexKey, $expiry)
  {
    if($this->isLocked($mutexKey))
    {
      $this->_memcache->set($mutexKey, $this->_getLockId(), null, $expiry);
    }
    return $this;
  }

  public function lockedBy($mutexKey)
  {
    return $this->_memcache->get($mutexKey) ?: null;
  }
}
