#!/bin/bash

set -e

if [ $# -ne 1 ]; then
  echo "Usage: `basename $0` <tag>"
  exit 65
fi

# CHECK MASTER BRANCH
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [[ "$CURRENT_BRANCH" != "master" ]]; then
  echo "You have to be on master branch currently on $CURRENT_BRANCH . Aborting"
  exit 65
fi

# CHECK FORMAT OF THE TAG 
php -r "if(preg_match('/^\d+\.\d+\.\d+(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?\$/',\$argv[1])) exit(0) ;else{ echo 'format of version tag is not invalid' . PHP_EOL ; exit(1);}" $1

# CHECK WE HAVE BOX INSTALLED
command -v box >/dev/null 2>&1 || { echo >&2 "This script requires box to be installed. Please see https://github.com/box-project/box2.  Aborting."; exit 1; }

# CHECK WE HAVE sha1sum INSTALLED
command -v sha1sum >/dev/null 2>&1 || { echo >&2 "This script requires sha1sum to be installed. Please run 'brew install md5sha1sum'.  Aborting."; exit 1; }

# CHECK THAT WE CAN CHANGE BRANCH
git checkout gh-pages
git checkout --quiet master

# 
TAG=$1

# TAG AND BUILD MASTER BRANCH
git checkout master
git tag ${TAG}

# BUILD THE APPLICATION
box build

# Move tracker so that it doesn't get overwritten
mv tracker.phar tracker

# Change to gh-pages branch
git checkout gh-pages

#Move the file back again
mv tracker tracker.phar

# Generate a sha1sum of the tracker file to track the version
sha1sum tracker.phar > tracker.phar.version

# Add tracker and version info to file
git add tracker.phar
git add tracker.phar.version

# Commit Latest Version
git commit -m "Bump version ${TAG}"

#Move back to the master branch
git checkout master

REMOTE=$(git remote)

echo ${REMOTE}

# Make sure to add in commit
echo "New version created. Now you should run:"
echo "git push origin gh-pages"
echo "git push origin ${TAG}"