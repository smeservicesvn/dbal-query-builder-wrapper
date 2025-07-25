#!/bin/sh
# setup-git-author.sh
#
# Usage:
#   ./setup-git-author.sh "Your Name" "your@email.com"
#
# This script sets the git user.name and user.email for the current repository only.
# Works on Mac (zsh/bash) and Linux.

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 \"Your Name\" \"your@email.com\""
  exit 1
fi

git config user.name "$1"
git config user.email "$2"

echo "Git author for this repo set to:"
echo "  Name : $(git config user.name)"
echo "  Email: $(git config user.email)"

# For Linux users, usage is the same:
#   ./setup-git-author.sh "Your Name" "your@email.com" 