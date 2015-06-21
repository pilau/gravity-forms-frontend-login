# Pilau Gravity Forms Frontend Login

A Gravity Forms add-on to provide frontend login functionality.

**NOTE:** Depends on the [Gravity Forms](https://github.com/gyrus/WordPress-Developers-Custom-Fields) plugin.

## Installation

Note that the plugin folder should be named `gravity-forms-frontend-login`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated.

## Usage notes

* When activated, if the login and lost password forms aren't present, they'll be imported.
* Don't change the titles of the forms - many actions are taken based on recognising the form by its title.
* Likewise, don't remove the default classes for the fields.
* Settings (under _Forms > Settings > Frontend Login_) include:
	* Roles to be blocked from the admin area
	* The page to redirect blocked users to