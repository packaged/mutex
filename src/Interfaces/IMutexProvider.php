<?php
namespace Packaged\Mutex\Interfaces;

use Packaged\Mutex\Exceptions\LockFailedException;

interface IMutexProvider
{
  /**
   * @param $lockId
   *
   * @return self
   */
  public function setLockId($lockId);

  /**
   * Lock the mutex
   *
   * @param string $mutexKey Identifier of the mutex.
   * @param int    $expiry   How long in seconds to keep the mutex locked just in
   *                         case the script dies. 0 = never expires.
   *
   * @return self
   * @throws LockFailedException
   */
  public function lock($mutexKey, $expiry);

  /**
   * Unlock the mutex if it is locked
   *
   * @param string $mutexKey Identifier of the mutex.
   *
   * @return self
   */
  public function unlock($mutexKey);

  /**
   * Check if we hold the lock for this key
   *
   * @param string $mutexKey Identifier of the mutex.
   *
   * @return bool
   */
  public function isLocked($mutexKey);

  /**
   * Touch the mutex to reset its expiry if we have it locked.
   *
   * @param string $mutexKey Identifier of the mutex.
   * @param int    $expiry   How long in the future to set the new expiry
   *
   * @return self
   */
  public function touch($mutexKey, $expiry);

  /**
   * Find out the ID of the process locking the mutex
   *
   * @param string $mutexKey The mutex to check
   *
   * @return string|null The name of the locking process or null if not locked
   */
  public function lockedBy($mutexKey);
}
