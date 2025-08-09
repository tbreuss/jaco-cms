# Jaco CMS

## Links

Article about testdox format
- <https://golangexample.com/turns-go-test-names-into-readable-sentences-in-testdox-format/>

Inspiration
- <http://www.coast-cms.de>

## Development Environment

### Clone project

Clone project

    git clone https://github.com/tbreuss/jaco-cms.git

Change directory

    cd jaco-cms

### Create Docker Image

Create Docker image based on the latest supported PHP version

    docker build -t jaco .

Optionally you can also use an older PHP version

    docker build --build-arg PHP_VERSION=8.1 -t jaco .
    docker build --build-arg PHP_VERSION=8.2 -t jaco .
    docker build --build-arg PHP_VERSION=8.3 -t jaco .

### Run Development Environment

Install packages

    docker run --rm -it -v .:/app jaco composer install -d /app/jaco/

Run website

    docker run --rm -v .:/app -p 8888:8888 jaco php -S 0.0.0.0:8888 -t /app

Debug website using Xdebug

    docker run --rm -e XDEBUG_CONFIG="client_host=172.17.0.1" -e XDEBUG_MODE=debug -e XDEBUG_SESSION_START=true -v .:/app -p 8888:8888 jaco php -S 0.0.0.0:8888 -t /app

### Static Code Analyses

PHP CS Fixer

    docker run --rm -v $(pwd)/jaco:/code ghcr.io/php-cs-fixer/php-cs-fixer:${FIXER_VERSION:-3-php8.1} fix
