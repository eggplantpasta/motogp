# MotoGP Bidding 🏍️

A redevelopment of [Noisy's](https://www.partymeeple.com.au/contact.html) original code.

## Design philosophy

MotoGP Bidding is intentionally built as a small, traditional PHP application.

The project favours explicit, easy-to-follow code over frameworks and architectural abstraction. Where practical, each page consists of one PHP file and one corresponding Mustache template. Reusable behaviour is kept in small application classes, SQL remains explicit, and external Composer dependencies are kept to a minimum.

When choosing between two reasonable implementations, prefer the one that can be understood by reading the relevant PHP file and template from top to bottom.

Modern security practices are still expected; simplicity should not come at the expense of security.

## Game rules

### Bidding

For each event:

- Each player may bid on three riders.
- A bid may be any whole number of points, including zero.
- The highest bid for a rider wins that rider.
- If two or more players tie for the highest bid, they all win that rider.

### Points pool

After bidding closes, the total points pool for the event is calculated from all winning bids:

**points pool = sum of all winning bids + number of winning bids**

Each winning bid therefore adds one new point to the game economy.

For example, if two players both make a winning bid of 5 points on the same rider, both bids are included in the pool. Subject to confirmation, those bids would contribute:

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

The percentages total 100% of the event points pool.

### Rules requiring clarification

The following details still need to be confirmed before the game logic is implemented:

- How are fractional payouts rounded?
- Does each player have to bid on exactly three different riders, or may they bid on fewer than three?
- Are the three bids deducted from the player's balance when submitted, or only winning bids?
- Can a player bid more points than their current balance?
- Can bids be changed or withdrawn while bidding remains open?
- When exactly does bidding close?
- Are bids hidden from other players until bidding closes?
- If multiple players tie for the highest bid on a rider, confirm that every tied winning bid is included separately in the points pool.
- If multiple players win the same rider, does each player receive the full payout for that rider's finishing position?
- What happens to a winning rider who does not start, does not finish, or finishes outside the top eight?


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

## About

Built using [PHP](https://www.php.net), [SQLite](https://sqlite.org), vanilla [JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript), [Mustache](https://mustache.github.io) templates, and [semantic HTML](https://developer.mozilla.org/en-US/docs/Glossary/Semantics#semantics_in_html).

Made pretty using [Pico CSS](https://picocss.com), [Lucide Icons](https://lucide.dev/icons/), [Font Awesome](https://fontawesome.com), and [Michroma](https://fonts.google.com/specimen/Michroma?preview.script=Latn) from [Google Fonts](https://fonts.google.com/?preview.script=Latn).
