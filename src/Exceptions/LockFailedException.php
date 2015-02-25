<?php
namespace Packaged\Mutex\Exceptions;

use Exception;

class LockFailedException extends \Exception
{
  public function __construct(
    $message = "", $code = 0, Exception $previous = null
  )
  {
    if(empty($message))
    {
      $message = 'Failed to lock';
    }
    parent::__construct($message, $code, $previous);
  }
}
