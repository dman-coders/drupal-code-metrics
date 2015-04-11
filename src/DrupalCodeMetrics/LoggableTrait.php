<?php
/**
 * @file
 * Adds the log() method to a class.
 *
 * My first experiment with traits. This project is a learning experience...
 */

namespace DrupalCodeMetrics;

/**
 * Loggable trait.
 */
trait LoggableTrait
{
  /**
   * Logs a message if verbose is set in the options.
   *
   * If the object has no options, log anyway.
   *
   * @param $message
   * @param string $label
   */
  private function log($message, $label = '') {
    if (isset($this->options) && empty($this->options['verbose'])) {
      return;
    }
    $out = $label ? $label . " : " : "";
    $out .= is_string($message) ? $message : var_export($message, 1);
    // Where this message is coming from is handy to know.
    $stack = debug_backtrace();
    $caller = $stack[0];
    $caller2 = $stack[1];
    $caller_info = "${caller['file']}:${caller['line']} ${caller2['function']}(); ";

    error_log($out . "\n    -- $caller_info");
  }
}
