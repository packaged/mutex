<?php
namespace Packaged\Mutex\Providers;

use Packaged\Mutex\Interfaces\IMutexProvider;

abstract class AbstractMutexProvider implements IMutexProvider
{
  private $_id;

  protected function _getLockId()
  {
    if($this->_id === null)
    {
      $this->_id = uniqid(gethostname() . ':' . getmypid() . '@', true);
    }
    return $this->_id;
  }

  public function setLockId($lockId)
  {
    $this->_id = $lockId;
    return $this;
  }
}
