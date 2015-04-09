<?php
/**
 * @file
 * Routines lifted right out of Drupal common.inc etc.
 */

/**
 * Parses Drupal module and theme .info files.
 *
 * Information stored in a module .info file:
 * - name: The real name of the module for display purposes.
 * - description: A brief description of the module.
 * - dependencies: An array of shortnames of other modules this module requires.
 * - package: The name of the package of modules this module belongs to.
 *
 * @param string $filename
 *   The file we are parsing. Accepts file with relative or absolute path.
 *
 * @return array
 *   The info array.
 *
 * @see drupal_parse_info_format()
 */
function drupal_parse_info_file($filename) {
  $info = array();

  if (!isset($info[$filename])) {
    if (!file_exists($filename)) {
      $info[$filename] = array();
    }
    else {
      $data = file_get_contents($filename);
      $info[$filename] = drupal_parse_info_format($data);
    }
  }
  return $info[$filename];
}

/**
 * Parses data in Drupal's .info format.
 *
 * @param string $data
 *   A string to parse.
 *
 * @return array
 *   The info array.
 *
 * @see drupal_parse_info_file()
 */
function drupal_parse_info_format($data) {
  $info = array();
  $constants = get_defined_constants();

  if (preg_match_all('
    @^\s*                           # Start at the beginning of a line, ignoring leading whitespace
    ((?:
      [^=;\[\]]|                    # Key names cannot contain equal signs, semi-colons or square brackets,
      \[[^\[\]]*\]                  # unless they are balanced and not nested
    )+?)
    \s*=\s*                         # Key/value pairs are separated by equal signs (ignoring white-space)
    (?:
      ("(?:[^"]|(?<=\\\\)")*")|     # Double-quoted string, which may contain slash-escaped quotes/slashes
      (\'(?:[^\']|(?<=\\\\)\')*\')| # Single-quoted string, which may contain slash-escaped quotes/slashes
      ([^\r\n]*?)                   # Non-quoted string
    )\s*$                           # Stop at the next end of a line, ignoring trailing whitespace
    @msx', $data, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
      // Fetch the key and value string.
      $i = 0;
      foreach (array('key', 'value1', 'value2', 'value3') as $var) {
        $$var = isset($match[++$i]) ? $match[$i] : '';
      }
      $value = stripslashes(substr($value1, 1, -1)) . stripslashes(substr($value2, 1, -1)) . $value3;

      // Parse array syntax.
      $keys = preg_split('/\]?\[/', rtrim($key, ']'));
      $last = array_pop($keys);
      $parent = &$info;

      // Create nested arrays.
      foreach ($keys as $key) {
        if ($key == '') {
          $key = count($parent);
        }
        if (!isset($parent[$key]) || !is_array($parent[$key])) {
          $parent[$key] = array();
        }
        $parent = &$parent[$key];
      }

      // Handle PHP constants.
      if (isset($constants[$value])) {
        $value = $constants[$value];
      }

      // Insert actual value.
      if ($last == '') {
        $last = count($parent);
      }
      $parent[$last] = $value;
    }
  }

  return $info;
}
