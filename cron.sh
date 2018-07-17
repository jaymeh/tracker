# import the projects
/usr/local/bin/tracker -n project-import > /Users/{username}/.tracker/import-log.txt

# get the last date we imported, and yesterdays date
fromdate="`cat /Users/{username}/.tracker/last-import.txt`"
todate="`date -v -1d +%d/%m/%Y`"

# import all records between the last successful import and yesterday
/usr/local/bin/tracker time-update custom $fromdate $todate > /Users/{username}/.tracker/tracker-log.txt

# put todays date in as the next 'from' date
date +%d/%m/%Y > /Users/{username}/.tracker/last-import.txt