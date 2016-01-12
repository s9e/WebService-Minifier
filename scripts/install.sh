#!/bin/bash

wget -cq http://dl.google.com/closure-compiler/compiler-latest.zip -O /tmp/compiler-latest.zip &
wget -cq https://getcomposer.org/composer.phar -O /tmp/composer.phar &
wait

cd "$(dirname $(dirname $0))/bin"
unzip -n /tmp/compiler-latest.zip compiler.jar
chmod 0444 compiler.jar

cd ..
php /tmp/composer.phar install

chmod +w storage
