Running these tools directly.

# PHP Mess Detector (phpmd)

  vendor/bin/phpmd /var/www/project/webroot/sites/all/modules/custom/ \
    text cleancode,codesize,design,naming,unusedcode

Explanations of what it is analysing are at http://phpmd.org/rules/codesize.html

@see http://phpmd.org/documentation/index.html

# PHP Depend (pdepend)

  ./vendor/bin/pdepend \
     --suffix="php,inc,module,install" \
     --summary-xml=/tmp/summary.xml \
     --jdepend-chart=/tmp/jdepend.svg \
     --overview-pyramid=/tmp/pyramid.svg \
       /var/www/drupalnetnz/dev/sites/all/modules/contrib/webform/

You MUST add additional file extensions to get a proper result.

Although there is a pdepend 'config' file, it is only for settings for the
 grpahic output, does not set default options or anything.

The graphs are MUCH more tuned towards large linked projects, and focusses on
class relationships etc. Most of that does not apply to non OO modules.
The summary-xml produces metrics like
http://pdepend.org/documentation/software-metrics/index.html

File:
 loc="101" * Lines Of Code
 cloc="19" * Comment Lines of Code
 ncloc="82" * Non Comment Lines Of Code (Includes whitespace)
 eloc="71" *  Executable Lines of Code (After stripping whitespace and comments - includes structure stuff, like "}" )
 lloc="20" * Logical Lines Of Code (Actual commands. Squashes multiline assignments, like arrays in hook_menu or FAPI. ignores "})" and pretty-printing.

Generally, this is not well suited to Drupal module evaluation.
Project:
 loc="15976"
 cloc="3285"
 ncloc="12691"
 eloc="11058"
 lloc="5104"

 calls="1882" * Functions etc called
 ccn2="2253" * Cyclomatic complexity (newer)
 nof="431" * Number of functions (eg, grep -r "function .*(" )
 nom="33" * Number of Methods

Cyclomatic complexity for any project is going to be high,
for it to have meaning, it should be at least divided by the number of
functions+methods
to get an average complexity. 2253/464 = 4.8.

http://en.wikipedia.org/wiki/Cyclomatic_complexity
http://phpmd.org/rules/codesize.html
5 is fine. 10 is considered high and should be refactored.

@see http://pdepend.org/documentation/getting-started.html

# PHP Lines of code (phploc)

  vendor/bin/phploc /var/www/project/webroot/sites/all/modules/custom/

Options  --names="*.php,*.inc,*.module,*.install,*.css,*.js"
may be neccessary.

Option --git-repository .
looks interesting.

PHPLOC crosses over with pdepend.
pdepend comes with phpmd, and seems *marginally* better supported.
However, the output from PHPLOC is much more usable.

NOTE, this tool triggers some PHP notices when run with error reporting on.
Just 'undefined index' in places, but it get in the way when running to the screen.
I edited the to ensure that when run on the cli it does not show that noise.

Edit vendor/phploc/phploc/phploc to read:

  #!/usr/bin/env php -d error_reporting=0

@see https://github.com/sebastianbergmann/phploc


