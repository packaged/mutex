<?php
namespace Packaged\Mutex\Providers;

use Packaged\Mutex\Exceptions\LockFailedException;

class MemcachedMutexProvider extends AbstractMutexProvider
{
  /**
   * @var \Memcached
   */
  private $_memcache;

  public function __construct(\Memcached $cachePool)
  {
    $this->_memcache = $cachePool;
  }

  /**
   * Lock the mutex
   *
   * @param string $mutexKey Identifier of the mutex.
   * @param int    $expiry   How long in seconds to keep the mutex locked just in
   *                         case the script dies. 0 = never expires.
   *
   * @throws LockFailedException
   */
  public function lock($mutexKey, $expiry)
  {
    // Try and set the value. If it fails check to see if
    // it already contains our ID
    if(!$this->_memcache->add($mutexKey, $this->_getLockId(), $expiry))
    {
      if(!$this->isLocked($mutexKey))
      {
        throw new LockFailedException();
      }
    }
  }

  /**
   * Unlock the mutex if it is locked
   *
   * @param string $mutexKey Identifier of the mutex.
   */
  public function unlock($mutexKey)
  {
    $this->_memcache->delete($mutexKey);
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
    $this->_memcache->set($mutexKey, $this->_getLockId(), $expiry);
  }
}
