<?php
namespace Packaged\Mutex\Providers;

use Packaged\Mutex\Exceptions\LockFailedException;
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
}
