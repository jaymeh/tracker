# import the projects
/usr/local/bin/tracker -n project-import > import-log.txt

# get the last date we imported, and todays date
fromdate="`cat last-import.txt`"
todate="`date +%d/%m/%Y`"

# import all records between the last successful import and today
/usr/local/bin/tracker time-update custom $fromdate $todate > tracker-log.txt

# put tomorrows date in as the next 'from' date
date -v +1d +%d/%m/%Y > last-import.txt