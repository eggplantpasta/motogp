# WebMin 👾

**A minimal PHP/SQLite website template.**

This template is intended to be used for quick non-production hobby websites. It has the following design goal:

* runnable from the PHP built in webserver to ease development straight from the repository
* minimal dependencies PHP,  SQLite, 

## Structure

The structure of the project folders conforms mostly to the  [Standard PHP Package Skeleton](https://github.com/php-pds/skeleton). Refer to that document to determine how to extend this template in an organised manner.

* **/bin** executable scripts such as `install.sh` and `serve.sh`
* **/config** various .ini files
* **/public** the entry point (web root)
* **/src** classes and other included PHP code
* **/db** DDL and DML used to build and seed the database as well as the SQLite data file itself

## Getting Started (Debian based Linux)

Install the prerequisites of PHP and SQLite.

```sh
# update the machine
sudo apt-get update && sudo apt-get -y upgrade && sudo apt -y autoremove --purge

# install php and sqllite
sudo apt install php-cli php-sqlite3 sqlite3

# optional GUI SQLite browser
sudo apt install sqlitebrowser
```

On the github repository page press the "use this template" button to "Create a new repository" (see [Creating a repository from a template](https://docs.github.com/en/repositories/creating-and-managing-repositories/creating-a-repository-from-a-template#creating-a-repository-from-a-template)).

Create a local config file based on the example and start the local PHP server.

```bash
bin/install.sh
bin/serve.sh
```

Go to [the website homepage](http://localhost:8080).

## Development

_content coming_

## Deployment

_content coming_

## References

* [SQLite](https://www.sqlite.org/)
* [PHP](https://www.php.net/) and [PHP The Right Way](https://phptherightway.com/)
* [Pico CSS](https://picocss.com/) Minimal CSS framework for semantic HTML
* [Standard PHP Package Skeleton](https://github.com/php-pds/skeleton)
* Stefan Huber's article on the [PHP built-in web server](https://stefanhuber.at/posts/php-builtin-webserver/)
* [PSR-4](https://www.php-fig.org/psr/psr-4/) and [Build your own PSR-4 autoloader](https://pretzelhands.com/posts/build-your-own-psr-4-autoloader/)

**PHP Manual >**

* [Built-in web server](https://www.php.net/manual/en/features.commandline.webserver.php)
* [Magic constants](https://www.php.net/manual/en/language.constants.magic.php)
* [parse_ini_file](https://www.php.net/manual/en/function.parse-ini-file.php)