# delete existing database and recreate empty tables with default data

# delete existing database
rm -f db.sqlite3

# create new database and tables
sqlite3 db.sqlite3 < schema.sql

# insert default data
sqlite3 db.sqlite3 < seed/motogp-calendar-2025.sql
sqlite3 db.sqlite3 < seed/motogp-riders-2025.sql
