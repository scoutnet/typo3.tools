#!/usr/bin/env bash

mkdir -p Classes/{Controller,Helpers,ViewHelpers,Domain/{Model,Repository}}

mkdir -p Configuration/TCA

mkdir -p Resources/{Private/{Language,Layouts,Partials,Templates},Public/{CSS,JS,Icons}}

cat $(dirname $0)/template_files/ext_emconf.template > ext_emconf.php

cat $(dirname $0)/template_files/ext_access_denied.template > ext_tables.php
cat $(dirname $0)/template_files/ext_access_denied.template > ext_localconf.php
