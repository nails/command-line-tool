# Command Line tool for Nails

![license](https://img.shields.io/badge/license-MIT-green.svg)
[![CircleCI branch](https://img.shields.io/circleci/project/github/nails/command-line-tool.svg)](https://circleci.com/gh/nails/command-line-tool)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nails/command-line-tool/badges/quality-score.png)](https://scrutinizer-ci.com/g/nails/command-line-tool)
[![Join the chat on Slack!](https://now-examples-slackin-rayibnpwqe.now.sh/badge.svg)](https://nails-app.slack.com/shared_invite/MTg1NDcyNjI0ODcxLTE0OTUwMzA1NTYtYTZhZjc5YjExMQ)

The command line tool for Nails makes creating new projects easy, and provides a simple interface to the installed console module.

## Installation:

### Using Homebrew
```bash
brew tap nails/utilities
brew install nails
```

### Using Composer
```bash
composer global require nails/command-line-tool
```

### Manually

1. Clone this repository
2. Add `dist` to your `$PATH`


## Usage

```bash
# Create a new Nails project in the active directory
nails new 

# Create a new Nails project in another directory
nails new --dir=~/my-project

# Clone all active official Nails repositories to the active directory – this is useful for contributing 
nails dev:pull
```

Execute `nails --help` for further information

If `nails` is called in a folder which contains a Nails installation then it will proxy the app's console. To view all available commands for your app simply call `nails` with no arguments in the app's root directory.
