# MotoGP Bidding 🏍️

A redevelopment of [Noisy's](https://www.partymeeple.com.au/contact.html) original code.

## Getting Started

Install the prerequisites of [PHP](https://www.php.net/), [SQLite](https://sqlite.org/), and [Composer](https://getcomposer.org/).

```sh
# update the machine
sudo apt-get update && sudo apt-get -y upgrade

# install php and sqllite
sudo apt install php-cli php-sqlite3 sqlite3

# optional GUI SQLite browser and composer
sudo apt install sqlitebrowser Composer
```

Run the scripts that install the defined dependencies via composer, create local config files based on the examples, and start the local PHP server.

```bash
bin/install.sh
bin/serve.sh
```

Go to [the website homepage](http://localhost:8080).

## Deploy
