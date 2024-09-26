<?php

use Drupal\acsf\Event\AcsfEventHandler;

/**
 * UnitTestDummyHandlerInterrupt.
 */
class UnitTestDummyHandlerInterrupt extends AcsfEventHandler {

  public $event;

  /**
   * Dummy handler.
   */
  public function handle() {
    $this->event->dispatcher->interrupt();
  }

}
