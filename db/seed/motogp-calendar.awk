#!/bin/awk -f

BEGIN {
    FS = ":"
    RS="\r\n" # deal with windows line endings
    printf "delete from events;\n"
    printf "insert into events (event_id, start_date, name) values\n"
    first = 1
    id = 1
}

/^DTSTART:/ {
    dtstart = substr($2, 1, 4) "-" substr($2, 5, 2) "-" substr($2, 7, 2) " " substr($2, 10, 2) ":" substr($2, 12, 2) ":" substr($2, 14, 2)
}

/^SUMMARY:/ {
    summary = $2
}

/^END:VEVENT/ {
    if (first) {
        first = 0
    } else {
        printf ",\n"
    }
    printf "(%d, '%s', '%s')", id++, dtstart, summary
}

END {
    printf ";"
}