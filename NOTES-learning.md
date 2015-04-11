# This whole project is experimental

.. as I learn the ropes with

* Composer
* Symfony
* Doctrine
* PSR and autoloading
* PHP namespaces
* PHP OO
* Traits

And things. I've never used *any* of these in any way before,
 so there are a few bits I'm making up as I go along.
 It's a lot to bite off in one go, and I get distracted by other bits as I go.
 Such as the libraries themselves
 - each of them probably deserves a weekend of playing with alone.

Therefore, the code may contain some laboriously extended examples that
 could be one-liners, but I unpack them to label the parts.

 ~dman 2015-040-10

# Additional notes

## XDebug on the CLI.

To enable XDEBUG on PHPStorm, set the environment variable

    export XDEBUG_CONFIG="idekey=PHPSTORM"

Then in PHPStorm click the 'Listen' button - next to but not inside the
 debugging settings. Do not need to set up a debug profile.

 (Xdebug must also be enabled in your php.ini already.)

 Breakboints shouold now work.
