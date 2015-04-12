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

## Method chaining

TL;DR:
When you can think of nothing better to return from an OO method,
return $this.

http://www.techflirt.com/tutorials/oop-in-php/php-method-chaining.html

Setters, and action triggers with no volatile reponses, can return the
 current object, so as to allow chaining such as

   $object
     ->setName('Harry')
     ->setStatus('updated')
     ->update();

It's the new sexy way it seems. It's been happening in jquery for ever,
and is spreading through the other languages now.
Already seen in the PHP Database Abstraction layers.

Note that method chaining leave less space for assertions or error catching!
This may in turn lead to more need for exception blocks in some cases.
Exception-based control flow is NOT a great paradigm.
So don't over-use method chaining when code can still be broken up into
intermediate steps for validation, logging, or even just code-tracing.

If setting out to use method chaining a lot, do step the commands out onto new
lines as illustrated above.

## Console output in Synfony

Don't bother with the Symfony\Component\Console\Helper\Table;
It's atrocious - does not handle wrapping - which is the one thing it would
be worth using a library for.
If the cols are to big, it just farts on the screen.

