<?php
namespace Packaged\Mutex\Interfaces;

use Packaged\Mutex\Exceptions\LockFailedException;

interface IMutexProvider
{
  /**
   * Lock the mutex
   *
   * @param string $mutexKey     Identifier of the mutex.
   * @param int    $expiry How long in seconds to keep the mutex locked just in
   *                       case the script dies. 0 = never expires.
   *
   * @throws LockFailedException
   */
  public function lock($mutexKey, $expiry);

  /**
   * Unlock the mutex if it is locked
   *
   * @param string $mutexKey Identifier of the mutex.
   */
  public function unlock($mutexKey);

  /**
   * @param string $mutexKey Identifier of the mutex.
   *
   * @return bool
   */
  public function isLocked($mutexKey);

  /**
   * Touch the mutex to reset its expiry if we have it locked.
   *
   * @param string $mutexKey     Identifier of the mutex.
   * @param int    $expiry How long in the future to set the new expiry
   */
  public function touch($mutexKey, $expiry);
}
