# PHP Util

### Install PHP on Ubuntu:

Docs: https://ubuntu.com/server/docs/programming-php

```
$ sudo apt-get update -y &&
sudo apt install php libapache2-mod-php -y &&
sudo apt install php-cli -y &&
sudo apt install php-cgi -y
```

### Extensions:

```
$ sudo apt install php-common -y &&
sudo apt install openssl -y &&
sudo apt install php-curl -y &&
sudo apt install php-mbstring -y &&
sudo apt install php-zip -y &&
sudo apt install php-imap -y &&
sudo apt install php-xml -y &&
sudo apt install php-soap -y
sudo apt install php-mysqli -y
```

### How to install Composer:

1:

```
$ php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

2:

```
$ sudo mv composer.phar /usr/local/bin/composer
```
