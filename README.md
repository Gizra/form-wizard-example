# Form Wizard Example

## Requirements

* [DDEV](https://ddev.readthedocs.io/en/stable/)

## Installation

    ddev composer install
    ddev restart

Notice that in the end of the `ddev restart` we get a one time admin link to login.

## Configuration management

Import configuration from code:
    ddev exec drush cim

Export active configuration into code:
    ddev exec drush cex

## Theme development

Generally for theme development, it's advisable to entirely turn off caching:
https://www.drupal.org/node/2598914

### Compile SASS

    ddev ssh
    npm install -g sass
    sass --watch ./themes/custom/theme_server/scss/style.scss:./themes/custom/theme_server/css/style.css

## Add new modules

With `composer require ...` you can download new dependencies to your
installation.

```
composer require drupal/devel:~1.0
```

## How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull
request is often a better solution), you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL or local path to patch"
        }
    }
}
```
