I'm learning Composer and Doctrine for the first time here.

The DATABASE schema is managed by Doctrine.
Specially constructed files in the /src/ directory are scanned.
Object definitions there define the data schemas, and the data schemas
are then automatically translated into database schemas when we run:

  vendor/bin/doctrine orm:schema-tool:create

After changing code, the schemas can be updated with:

  vendor/bin/doctrine orm:schema-tool:update --force

Or rebuilt with:

  vendor/bin/doctrine orm:schema-tool:drop --force
  vendor/bin/doctrine orm:schema-tool:create

