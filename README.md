# MotoGP Bidding 🏍️

A redevelopment of [Noisy's](https://www.partymeeple.com.au/contact.html) original code.

## Development

Install the prerequisites of [PHP](https://www.php.net/), [SQLite](https://sqlite.org/), and [Composer](https://getcomposer.org/).

```sh
# update the machine
sudo apt-get update && sudo apt-get -y upgrade

# install php and sqllite  and composer
sudo apt install php-cli php-sqlite3 sqlite3 composer

# optional GUI SQLite browser
sudo apt install sqlitebrowser
```

Clone the repo.

```bash
git clone https://github.com/eggplantpasta/motogp.git
cd motogp
```

Run the scripts that install the defined dependencies via composer, create local config files based on the examples, and start the local PHP server.

```bash
bin/install-local.sh
bin/serve-local.sh
```

Go to [the website homepage](http://localhost:8080).

## Deploy

TODO

## About

Built using [PHP](https://www.php.net), [SQLite](https://sqlite.org), vanilla [JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript), [Mustache](https://mustache.github.io) templates, and [semantic HTML](https://developer.mozilla.org/en-US/docs/Glossary/Semantics#semantics_in_html).

Made pretty using [Pico CSS](https://picocss.com), [Lucide Icons](https://lucide.dev/icons/), and [Michroma](https://fonts.google.com/specimen/Michroma?preview.script=Latn) from [Google Fonts](https://fonts.google.com/?preview.script=Latn).
