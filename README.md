#Command Line tool for Nails

This is the command line tool for Nails. It is an installable executeable which makes it easy to create new Nails apps, install (or reinstall) them, or to migrate databases.

##Installation:

###Using Homebrew
1. Tap Nails using `brew tap nailsapp/nailsapp`
2. Install using `brew install nails`
3. Update as normal using `brew update && brew upgrade`

###Manually

1. Clone this repository
2. Create a symlink of the executable
3. Place the symlink somewhere in your PATH
4. To update, simply `git pull origin master`

##Usage

You can pass one of three arguments to the tool:

- `new` will clone the skeleton repository, install all the dependancies, run the Nails installer and then prepare a new repository for the app
- `install` will run the Nails installer (you can also run it directly by issuing `./nails.php install`, but this way is cleaner)
- `install` will run the Nails Database Migration tool (you can also run it directly by issuing `./nails.php migrate`, but this way is cleaner)


###Prerequisites
- Composer is installed
- Git is installed
- Git flow extension is installed

Docs: http://nailsapp.co.uk/console/tool