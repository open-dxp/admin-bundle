#!/bin/bash

#
# ATTENTION!
# This file is deprecated and will be removed with admin-bundle 2.0
#

docker-compose down -v --remove-orphans
docker-compose up -d

docker-compose exec php .github/ci/scripts/setup-opendxp-environment.sh

docker-compose exec php composer update

printf "\n\n\n================== \n"
printf "Run 'docker-compose exec php vendor/bin/codecept run -vv' to re-run the tests.\n"
printf "Run 'docker-compose down -v --remove-orphans' to shutdown container and cleanup.\n\n"