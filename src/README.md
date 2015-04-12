# Autoloading classes from here

@tag tutorial

Class definitions found in here will become available for autoloading
thanks to the line in composer.json

  "autoload": {
    "psr-0": {
      "": "src/"
    }
  },


Class definitions found here that declare themselves to be an @Entity
will be used to define our database schema implicitly,
thanks to the
  createAnnotationMetadataConfiguration()
setup found in bootstrap.php
