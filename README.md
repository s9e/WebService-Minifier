Experimental minifier service to be used with [s9e\\TextFormatter](https://github.com/s9e/TextFormatter/).

### Installation

Run `scripts/install.sh`. It will download the [latest release of Google Closure Compiler](http://dl.google.com/closure-compiler/compiler-latest.zip), copy `compiler.jar` to the `bin` directory, run composer and make `www/cache` writable.

Only the `www` directory needs to be publicly accessible.

### API

`www/minify.php` accepts POST requests, receives the original source code as raw data in the request's body and returns the minified code in the response body.

### Maintenance

There are no limits on the cache dir. You can periodically run `scripts/tidy.php 123` to delete all but the newest `123` MB of data from the cache.
