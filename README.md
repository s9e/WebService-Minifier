Experimental minifier service to be used with [s9e\\TextFormatter](https://github.com/s9e/TextFormatter/).

### Installation

Run `scripts/install.sh`. It will download the [latest release of Google Closure Compiler](http://dl.google.com/closure-compiler/compiler-latest.zip), copy `compiler.jar` to the `bin` directory, run composer and make the `storage` directory writable.

Only the `www` directory needs to be publicly accessible.

### API

`www/index.php` accepts two kinds of requests:

 * a POST request with the original source code as raw data in the request's body will return the minified code in the response body.
 * a GET request with a `hash` query parameter will return the corresponding minified code from the cache if available.

### Maintenance

There are no limits on the cache dir. You can periodically run `scripts/tidy.php 123` to delete all but the newest `123` MB of data from the cache.
