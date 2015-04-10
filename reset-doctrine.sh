#!/bin/bash


  vendor/bin/doctrine orm:schema-tool:drop --force
  vendor/bin/doctrine orm:schema-tool:create

