#!/bin/sh

set -e

PHP=/opt/remi/php72/root/usr/bin/php

run_testsuite() {
    PHPUNIT=/usr/share/tuleap/tests/rest/vendor/bin/phpunit
    if [ -x $PHP ]; then
        PHPUNIT="$PHP $PHPUNIT"
    fi
    $PHPUNIT --configuration /usr/share/tuleap/tests/rest/phpunit.xml --do-not-cache-result --log-junit /output/rest_tests.xml $1
}

run_testsuite $1
