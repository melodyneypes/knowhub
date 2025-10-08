#!/bin/bash
# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies
composer install

# Make the PHP server executable
chmod +x vendor/bin/heroku-php-apache2