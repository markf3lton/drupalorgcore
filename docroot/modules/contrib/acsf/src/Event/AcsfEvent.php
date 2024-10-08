<?php

namespace Drupal\acsf\Event;

use Drupal\acsf\AcsfLog;
use Drupal\acsf\AcsfSite;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ACSF Event.
 *
 * An event within the ACSF framework encapsulates a dispatcher and a list of
 * event handlers. The event will contain an internal context that is accessible
 * from the handlers.
 *
 * $type = 'site_duplication_scrub';
 * $registry = acsf_get_registry();
 * $context = array('key' => 'value');
 * $event = new AcsfEvent(
 *   new AcsfEventDispatcher(),
 *   new AcsfLog(),
 *   $type,
 *   $registry,
 *   $context);
 * $event->run();
 */
class AcsfEvent {

  /**
   * Internal list of handlers, stored by type: incomplete / complete / failed.
   *
   * @var array[]
   */
  protected $handlers;

  /**
   * Handles log messages.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public $output;

  /**
   * The event dispatcher object.
   *
   * @var AcsfEventDispatcher
   */
  public $dispatcher;

  /**
   * The log object.
   *
   * @var \Drupal\acsf\AcsfLog
   */
  public $log;

  /**
   * The type of event to run.
   *
   * @var string
   */
  public $type;

  /**
   * The site being operated upon (optional).
   *
   * @var \Drupal\acsf\AcsfSite
   */
  public $site;

  /**
   * The registry from acsf_registry.
   *
   * @var array
   */
  public $registry;

  /**
   * An arbitrary context for handlers.
   *
   * @var array
   */
  public $context;

  /**
   * Constructor.
   *
   * @param AcsfEventDispatcher $dispatcher
   *   The event dispatcher object.
   * @param \Drupal\acsf\AcsfLog $log
   *   The log object.
   * @param string $type
   *   The type of event to run.
   * @param array $registry
   *   The registry from acsf_registry.
   * @param array $context
   *   An arbitrary context for handlers.
   * @param \Drupal\acsf\AcsfSite $site
   *   The site being operated upon (optional).
   */
  public function __construct(AcsfEventDispatcher $dispatcher, AcsfLog $log, $type, array $registry, array $context, AcsfSite $site = NULL) {
    $this->dispatcher = $dispatcher;
    $this->log = $log;
    $this->type = $type;
    $this->site = $site;
    // Make sure 'events' has a value so code can always refer to it.
    if (!isset($registry['events'])) {
      $registry['events'] = [];
    }
    $this->registry = $registry;
    $this->context = $context;
    $this->handlers = [
      'incomplete' => [],
      'complete' => [],
      'failed' => [],
    ];
  }

  /**
   * Creates an event using ACSF defaults.
   *
   * @param string $type
   *   The type of event to execute.
   * @param array $context
   *   A custom context to pass to event handlers.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The class that handles the logging messages.
   *
   * @return static
   *   Returns an instance of this class.
   */
  public static function create($type, array $context = [], OutputInterface $output = NULL) {
    $registry = acsf_get_registry();
    $event = new static(
      new AcsfEventDispatcher(),
      new AcsfLog(),
      $type,
      $registry,
      $context);
    $event->output = $output;

    return $event;
  }

  /**
   * Produces data that can be used to track and debug an event.
   */
  public function debug() {
    $debug = [];

    foreach (array_keys($this->handlers) as $key) {
      foreach ($this->handlers[$key] as $handler) {
        $debug['handlers'][$key][] = [
          'class' => get_class($handler),
          'started' => $handler->started,
          'completed' => $handler->completed,
          'message' => $handler->message,
        ];
      }
    }

    return $debug;
  }

  /**
   * Loads event handlers for the appropriate event.
   */
  protected function loadHandlers() {
    foreach ($this->registry['events'] as $info) {
      if ($info['type'] == $this->type) {
        $class = $info['class'];

        // Classes may still define a 'path' entry specifying the full path to
        // the class file, though using autoloading is preferred.
        if (!empty($info['path'])) {
          $path = trim($info['path'], '/');
          require_once sprintf('%s/%s/%s.php', DRUPAL_ROOT, $path, $class);
        }

        $this->pushHandler(new $class($this), 'incomplete');
      }
    }
  }

  /**
   * Pops (actually shifts to preserve order) a handler from the internal list.
   *
   * @param string $type
   *   The type of handler: incomplete, complete or failed.
   *
   * @return AcsfEventHandler
   *   The next event handler.
   *
   * @throws \Drupal\acsf\Event\AcsfEventHandlerIncompatibleException
   */
  public function popHandler($type = 'incomplete') {
    if (array_key_exists($type, $this->handlers)) {
      return array_shift($this->handlers[$type]);
    }
    else {
      throw new AcsfEventHandlerIncompatibleException(sprintf('The handler type "%s" is incompatible with this event.', $type));
    }
  }

  /**
   * Pushes a handler to in internal list.
   *
   * @param AcsfEventHandler $handler
   *   The handler to add.
   * @param string $type
   *   The type of handler: incomplete, complete or failed.
   *
   * @throws \Drupal\acsf\Event\AcsfEventHandlerIncompatibleException
   */
  public function pushHandler(AcsfEventHandler $handler, $type = 'incomplete') {
    if (array_key_exists($type, $this->handlers)) {
      $this->handlers[$type][] = $handler;
    }
    else {
      throw new AcsfEventHandlerIncompatibleException(sprintf('The handler type "%s" is incompatible with this event.', $type));
    }
  }

  /**
   * Dispatches all event handlers.
   */
  public function run() {
    $this->loadHandlers();
    $this->dispatcher->dispatch($this);
  }

}
