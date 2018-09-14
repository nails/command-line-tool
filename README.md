# Command Line tool for Nails

[![Join the chat on Slack!](https://now-examples-slackin-rayibnpwqe.now.sh/badge.svg)](https://nails-app.slack.com/shared_invite/MTg1NDcyNjI0ODcxLTE0OTUwMzA1NTYtYTZhZjc5YjExMQ)

This is the command line tool for Nails. It is an installable executeable which makes it easy to create new
Nails apps, install (or reinstall) them, or to migrate databases.

## Installation:

### Using Homebrew
1. Tap Nails using `brew tap nails/nails`
2. Install using `brew install nails`
3. Update as normal using `brew update && brew upgrade`

### Manually

1. Clone this repository
2. Create a symlink of the executable
3. Place the symlink somewhere in your PATH
4. To update, simply `git pull origin master`

## Usage

You have a new binary (well, technically a shell script) called `nails`. You can pass one of the following
arguments to the tool:

- `nails new folderName` will clone the skeleton repository into `folderName`, install all the dependancies,
  run the Nails Installer and then prepare a new repository for the app.
- `nails upgrade` will update all dependencies to their latest version then run the Nails Migration tool.
- `nails test` will run PHPUnit tests for the application.
- `nails dev` Shows available dev tools.
- `nails dev pull` Pull down all public Nails repositories from GitHub

The tool also wraps the bundled console application within Nails applications and makes them available as
additional commands. to view all available commands for your app simply call `nails` with no arguments in
the app's root directory.


## Prerequisites
- Composer is installed
- Bower is installed
- Git is installed
- Git flow extension is installed