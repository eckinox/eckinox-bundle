#!/bin/sh

# This script can be called within a 'post-merge' hooks into production

# Might be needed into the HOOKS post-merge script
# PATH_REPO=`git rev-parse --show-toplevel 2> /dev/null`

bin/console cache:clear
bin/console doctrine:migrations:diff
bin/console --no-interaction doctrine:migrations:migrate
bin/console eckinox:update
