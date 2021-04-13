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

  public function isLocked($mutexKey)
  {
    return ($this->_memcache->get($mutexKey) == $this->_getLockId());
  }

  public function touch($mutexKey, $expiry)
  {
    if($this->isLocked($mutexKey))
    {
      $this->_memcache->set($mutexKey, $this->_getLockId(), $expiry);
    }
    return $this;
  }

  public function lockedBy($mutexKey)
  {
    return $this->_memcache->get($mutexKey) ?: null;
  }
}
