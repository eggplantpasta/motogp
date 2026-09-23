# MotoGP Bidding 🏍️

A redevelopment of [Noisy's](https://www.partymeeple.com.au/contact.html) original code.

## Design philosophy

MotoGP Bidding is intentionally built as a small, traditional PHP application.

The project favours explicit, easy-to-follow code over frameworks and architectural abstraction. Where practical, each page consists of one PHP file and one corresponding Mustache template. Reusable behaviour is kept in small application classes, SQL remains explicit, and external Composer dependencies are kept to a minimum.

When choosing between two reasonable implementations, prefer the one that can be understood by reading the relevant PHP file and template from top to bottom.

Modern security practices are still expected; simplicity should not come at the expense of security.

### Game rules

### Bidding

For each event:

- Each player must bid on exactly three riders.
- A bid may be any whole number of points, including zero.
- The total of a player's three bids cannot exceed their current balance.
- The highest bid for a rider wins that rider.
- If two or more players tie for the highest bid, they all win that rider.
- Only winning bids are deducted from a player's balance. Losing bids cost nothing.
- Players may change their bids while bidding remains open.
- Bids are visible to other players.
- Bidding closes at a lockout time selected for the event.

### Points pool

After bidding closes, the total points pool for the event is calculated from all winning bids:

**points pool = sum of all winning bids + number of winning bids**

Each winning bid therefore adds one new point to the game economy.

For example, if two players both make a winning bid of 5 points on the same rider, both winning bids are included in the pool:

**5 + 5 + 2 = 12 points**

### Payouts

The points pool is distributed according to the finishing positions of the riders won by players.

| Position | Payout |
| --- | ---: |
| 1st | 23% |
| 2nd | 18% |
| 3rd | 15% |
| 4th | 12% |
| 5th | 11% |
| 6th | 8% |
| 7th | 7% |
| 8th | 6% |

Only riders finishing in the top eight receive a payout.

Fractional payouts are rounded up to the next whole point.

### Tied winning bids

If multiple players tie for the highest bid on the same rider, each tied bid is a winning bid:

- each winning bid is deducted from its player's balance;
- each winning bid is included separately when calculating the points pool; and
- the tied players share the payouts for the finishing positions they collectively occupy.

For example, if two players jointly win a rider and that rider finishes first, they share the first- and second-place payouts:

**(23% + 18%) / 2 = 20.5% each**

If three players jointly win a rider that finishes first, they share the first-, second- and third-place payouts:

**(23% + 18% + 15%) / 3 = 18.67% each**

The resulting points payout for each player is rounded up to a whole point.

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

This project supports both application-level and OS-level log rotation.

1. App-level rotation (Monolog):
Configure `log.days` in your `config/app.ini`.

```ini
[log]
path = "{{ROOT_DIR}}/var/log/app.log"
level = "info"
days = 30
```

2. OS-level rotation (logrotate):
Install the provided config for `php_error.log`:

```bash
sudo cp bin/logrotate-motogp.conf /etc/logrotate.d/motogp
sudo logrotate -d /etc/logrotate.d/motogp
```

The default policy in `bin/logrotate-motogp.conf` rotates daily, keeps 30 days, and compresses older `php_error.log` files.

Note: app logs are date-rotated by Monolog (`app-YYYY-MM-DD.log`) using `log.days`.

## Tooling

The repository includes configuration for the formatting and static-analysis tools used during development.

### VS Code

When the repository is opened in VS Code, install the extensions suggested by `.vscode/extensions.json`. The workspace settings in `.vscode/settings.json` configure formatting on save for PHP, JavaScript, and Mustache templates, and enable PHPStan using the project configuration.

The recommended extensions include:

- PHP CS Fixer for PHP formatting;
- PHPStan for PHP static analysis;
- Mustache language support;
- Prettier for JS and Mustache formatting
- SQLite support; and
- EditorConfig support.

Prettier is used as the formatter for JavaScript and Mustache files.

### PHP

PHP development tools are installed through Composer; JavaScript and Mustache development tools are installed through npm:

```bash
composer install
npm install
```

Before committing a significant change, the useful verification pass is:

```bash
composer format-check
composer analyse
npm run format-check
```

## About

Built using [PHP](https://www.php.net), [SQLite](https://sqlite.org), vanilla [JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript), [Mustache](https://mustache.github.io) templates, and [semantic HTML](https://developer.mozilla.org/en-US/docs/Glossary/Semantics#semantics_in_html).

Made pretty using [Pico CSS](https://picocss.com), [Lucide Icons](https://lucide.dev/icons/), [Font Awesome](https://fontawesome.com), and [Michroma](https://fonts.google.com/specimen/Michroma?preview.script=Latn) from [Google Fonts](https://fonts.google.com/?preview.script=Latn).
