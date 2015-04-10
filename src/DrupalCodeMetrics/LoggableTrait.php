<?php
/**
 * @file
 * Adds the log() method to a class.
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
   * @param $message
   * @param string $label
   */
  private function log($message, $label = '') {
    if (! $this->options['verbose']) {
      return;
    }
    $out = $label ? $label . " : " : "";
    $out .= is_string($message) ? $message : var_export($message, 1);
    error_log($out);
  }
}
