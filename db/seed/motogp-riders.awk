#!/bin/awk -f

BEGIN {
    printf "delete from riders;\n"
    printf "insert into riders (rider_id, name, active) values\n"
    first = 1
    id = 1
    name = ""
}

# start of MotoGP rider list
/rider-grid__motogp/ {
    motogp = 1
}

# end of MotoGP rider list
/rider-grid__moto2/ {
    motogp = 0
}

/<div class="rider-list__info-name">/ {
    rider = 1
}

/<span>/ {
    if (rider && motogp) {
        sub(/.*<span>[ \t]*/, "") # remove everything up to and including the span tag and leading whitespace
        sub(/[ \t]*<\/span>.*/, "") # remove everything after and including the span tag and trailing whitespace

        sub(/&amp;/, "&") # replace HTML encoded ampersand with actual ampersand
        sub(/&apos;/, "'") # replace HTML encoded apostrophe with actual apostrophe
        sub(/&quot;/, "\"") # replace HTML encoded double quote with actual double quote
        sub(/&lt;/, "<") # replace HTML encoded less than with actual less than
        sub(/&gt;/, ">") # replace HTML encoded greater than with actual greater than

        sub(/'/, "''") # escape single quotes for SQL
        sub(/"/, "\"\"") # escape double quotes for SQL

        name = ((name " ") $0) # append to name with a space between
        gsub(/^ /, "",  name) # remove leading space from concatenated name

        next # look for another name
    }
}

/<\/div>/ {
    if (rider && motogp) {
        if (first) {
            first = 0
        } else {
            printf ",\n"
        }
        printf "(%d, '%s', 1)", id++, name
        rider = 0
        name = ""
    }
}

END {
    printf ";\n"
} 
