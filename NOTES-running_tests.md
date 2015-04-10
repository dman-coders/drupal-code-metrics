Running these tools directly.

# PHP Mess Detector

  vendor/bin/phpmd /var/www/project/webroot/sites/all/modules/custom/ text cleancode,codesize,design,naming,unusedcode

Explanations of what it is analysing are at http://phpmd.org/rules/codesize.html

@see http://phpmd.org/documentation/index.html


# PHP Lines of code

  vendor/bin/phploc /var/www/project/webroot/sites/all/modules/custom/

Options  --names="*.php,*.inc,*.module,*.install,*.css,*.js"
may be neccessary.

Option --git-repository .
looks interesting.

NOTE, this tool triggers some PHP notices when run with error reporting on.
Just 'undefined index' in places, but it get in the way when running to the screen.
I edited the to ensure that when run on the cli it does not show that noise.

Edit vendor/phploc/phploc/phploc to read:

  #!/usr/bin/env php -d error_reporting=0

@see https://github.com/sebastianbergmann/phploc


