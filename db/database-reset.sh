# delete existing database and recreate empty tables with default data

# delete existing database
rm -f data.sqlite

# create new database and tables
sqlite3 data.sqlite < schema.sql

# seed default data
sqlite3 data.sqlite < seed/motogp-calendar-2025.sql
sqlite3 data.sqlite < seed/motogp-riders-2025.sql
