# delete existing database and recreate empty tables with default data

# delete existing database
rm -f db.sqlite3

# create new database and tables
sqlite3 db.sqlite3 -bail < schema.sql

# seed reference data
sqlite3 db.sqlite3 -bail < seed/countries.sql
sqlite3 db.sqlite3 -bail < seed/motogp-calendar-2026.sql
sqlite3 db.sqlite3 -bail < seed/motogp-riders-2026.sql

# seed test data
sqlite3 db.sqlite3 -bail < test-data/results.sql
sqlite3 db.sqlite3 -bail < test-data/users.sql
