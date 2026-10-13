# Jaco CMS

## Development Environment

### Clone project

Clone project

    git clone https://github.com/tbreuss/jaco-cms.git

Change directory

    cd jaco-cms

### Create Docker Image

Create Docker image based on the latest supported PHP version 8.5

    docker build -t jaco .

Optionally you can also use an older PHP version

    docker build --build-arg PHP_VERSION=8.2 -t jaco .
    docker build --build-arg PHP_VERSION=8.3 -t jaco .
    docker build --build-arg PHP_VERSION=8.4 -t jaco .

### Run Development Environment

Install packages

    docker run --rm -it -v .:/app jaco composer install -d /app/jaco/

Run website

    docker run --rm -v .:/app -p 8888:8888 jaco php -S 0.0.0.0:8888 -t /app

Debug website using Xdebug

    docker run --rm -e XDEBUG_CONFIG="client_host=172.17.0.1" -e XDEBUG_MODE=debug -e XDEBUG_SESSION_START=true -v .:/app -p 8888:8888 jaco php -S 0.0.0.0:8888 -t /app

Run CLI (`-w` is required, cli.php loads the autoloader relative to the working directory)

    docker run --rm -v .:/app -w /app/jaco jaco php cli.php sys:version
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php sys:log -s 20
    docker run --rm -v .:/app -w /app/jaco jaco php cli.php auth:token -u demo -p demo

### Static Code Analyses & Unit Tests

The dev tools are Composer dev dependencies; inside Docker they run through
the image built above (which already contains the installed packages):

PHP CS Fixer (check only, `cs-fix` to fix)

    docker run --rm -v .:/app jaco composer cs-check -d /app/jaco

PHPStan

    docker run --rm -v .:/app jaco composer phpstan -d /app/jaco

Deptrac (architecture rules, see `jaco/deptrac.yaml`)

    docker run --rm -v .:/app jaco composer deptrac -d /app/jaco

List what points at third party libraries (they are outside the layers)

    docker run --rm -v .:/app jaco composer deptrac -d /app/jaco -- --report-uncovered

PHPUnit (extra options after `--`, e.g. `-- --filter AuthTest`)

    docker run --rm -v .:/app jaco composer test -d /app/jaco

Everything at once

    docker run --rm -v .:/app jaco composer check -d /app/jaco

Code coverage with the gate. Needs a coverage driver, so run it with the xdebug image or with pcov: the gate fails when lines or methods drop below the minimum in `jaco/test/coverage-gate.php`, the class ratio is not checked because the marker exceptions have no lines.

    docker run --rm -e XDEBUG_MODE=coverage -v .:/app jaco composer coverage -d /app/jaco

Composer audit (security advisories; one known php-jwt low is ignored in composer.json)

    docker run --rm -v .:/app jaco composer audit -d /app/jaco

Composer validate (strict)

    docker run --rm -v .:/app jaco composer validate --strict -d /app/jaco

JavaScript syntax check (needs node >= 22, vendored assets excluded)

    find jaco/frontend jaco/test/js \( -name '*.js' -o -name '*.mjs' \) -not -path '*/asset/*' -exec node --check {} \;

QUnit (frontend, runs in headless Chrome, needs node >= 22)

    php -S 127.0.0.1:8899 -t . &
    node jaco/test/js/runner.mjs

## Links

Article about testdox format
- <https://golangexample.com/turns-go-test-names-into-readable-sentences-in-testdox-format/>

Inspiration
- <http://www.coast-cms.de>
