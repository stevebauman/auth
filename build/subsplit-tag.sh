#!/bin/sh

if [ -z "$1" ]; then
    echo "No argument supplied";
    exit 1;
fi

if [ -d .subsplit ]; then
    git subsplit update
else
    git subsplit init git@github.com:orchestral/auth.git
fi

git subsplit publish --heads="master 3.0 3.1 3.2" --tags=$1 src/Authorization:git@github.com:orchestral/authorization.git
