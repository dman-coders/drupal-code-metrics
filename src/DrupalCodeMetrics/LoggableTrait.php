<?php

namespace DrupalCodeMetrics;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Loggable trait.
 *
 * When instantiating a thing with a loggable trait, you can optionally pass it
 * a Symfony\Component\Console\Output\OutputInterface handle, eg by setting.
 *
 * '''' $o->setOutput($output);
 *
 * If no $output is set, then it will just print to screen,
 * but not be influenced by cli options like -v
 *
 * With that in place, the Console -v, -vv and -q flags will be respected.
 *
 * I want to re-use the trait freely without imposing requirements on the
 * implementors, so passing in an $output or $progress handle is totally
 * optional.
 *
 * Objects that use this trait will be able to Use either
 * $this->log('message')
 * or
 * $this->output->writeln('message')
 * as they please.
 *
 */
trait LoggableTrait {
  /**
   * @var OutputInterface
   */
  protected $output;

 /**
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  public function setOutput($output) {
    $this->output = $output;
  }

  /**
   * Logs a message to the current output stream.
   *
   * It is influenced by -q and -v flags.
   *
   * @param string|object|array $message
   *   Message to display.
   * @param string $label
   *   Additional label.
   *   if the $label is 'progress' it may be applied to the progress bar.
   *   (if in effect)
   * @param int $level
   *   1 (default) is normal output verbosity.
   *   Set the $level higher to suppress the output unless
   *   the -v or -vv flags are given.
   *   A log message with $level 2 will show up only if -v is given,
   *   A log message with $level 3 will show up only if -vv is set.
   */
  private function log($message, $label = '', $level = 1) {
    // Symfony has built-in support for -v, -vv, -vvv verbosity flags.
    // http://blog.calevans.com/2013/07/10/managing-the-verbosity-of-symfonys-command-object-with-a-trait/
    // 0 is quiet,.
    if ($this->output) {
      $verbosity = $this->output->getVerbosity();
    }
    else {
      $verbosity = OutputInterface::VERBOSITY_NORMAL;
    }

    if ($verbosity < $level) {
      return;
    }

    $out = $label ? $label . " : " : "";
    $out .= is_string($message) ? $message : var_export($message, 1);

    if ($verbosity > OutputInterface::VERBOSITY_VERBOSE) {
      // Where this message is coming from is handy to know when debugging.
      $stack = debug_backtrace();
      $caller = $stack[0];
      $caller2 = $stack[1];
      $caller_info = "${caller['file']}:${caller['line']} ${caller2['function']}(); ";
      $out .= "\n    -- $caller_info";
    }

    // If we are running with a progress bar on, then this is a message.
    if (isset($this->progress) && $label == 'progress') {
      $this->progress->setMessage($out);
    }
    else {
      if (isset($this->progress)) {
        $this->progress->clear();
        // Carrage return (no newline)
        $this->output->write("\x0D");
      }
      if (isset($this->output)) {
        $this->output->writeln($out);
      }
      else {
        error_log("\n" . $out);
      }
      if (isset($this->progress)) {
        $this->progress->display();
      }
    }
  }

}
