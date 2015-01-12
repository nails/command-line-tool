# Command Line tool for Nails

This is the command line tool for Nails. It is an installable executeable which makes it easy
to create new Nails apps, install (or reinstall) them, or to migrate databases.

## Installation:

### Using Homebrew
1. Tap Nails using `brew tap nailsapp/nailsapp`
2. Install using `brew install nails`
3. Update as normal using `brew update && brew upgrade`

### Manually

1. Clone this repository
2. Create a symlink of the executable
3. Place the symlink somewhere in your PATH
4. To update, simply `git pull origin master`

## Usage

You have a new binary (well, technically a shell script) called `nails`. You can pass one of
three arguments to the tool:

- `nails new` will clone the skeleton repository, install all the dependancies, run the Nails
   Installer and then prepare a new repository for the app. This is your first step for a new
   Nails site.
- `nails install` will check dependencies are installed then run the Nails Installer.
- `nails migrate` will check dependencies are installed then run the Nails Migration tool.
- `nails upgrade` will update all dependencies to their latest version then run the Nails
  Migration tool.


## Prerequisites
- Composer is installed
- Git is installed
- Git flow extension is installed