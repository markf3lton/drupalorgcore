<?php

use Drupal\acsf\Event\AcsfEventHandler;

/**
 * UnitTestDummyHandler1.
 */
class UnitTestDummyHandler1 extends AcsfEventHandler {

  public $event;

  /**
   * Dummy handler.
   */
  public function handle() {}

}
