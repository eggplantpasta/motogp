# delete existing database and recreate empty tables with default data

# delete existing database
rm -f db.sqlite3

# create new database and tables
sqlite3 db.sqlite3 < schema.sql

# seed default data
sqlite3 db.sqlite3 < seed/country.sql
sqlite3 db.sqlite3 < seed/motogp-calendar-2026.sql
sqlite3 db.sqlite3 < seed/motogp-riders-2026.sql
