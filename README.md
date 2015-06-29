# Pilau Gravity Forms Frontend Login

A Gravity Forms add-on to provide frontend login functionality.

**NOTE:** Depends on the [Gravity Forms](http://www.gravityforms.com/) plugin.

## Installation

Note that the plugin folder should be named `gravity-forms-frontend-login`. This is because if the [GitHub Updater plugin](https://github.com/afragen/github-updater) is used to update this plugin, if the folder is named something other than this, it will get deleted, and the updated plugin folder with a different name will cause the plugin to be silently deactivated.

## Usage notes

* When activated, if the login form isn't present, it'll be imported.
* Don't change the titles of the form - many actions are taken based on recognising the form by its title.
* Likewise, don't remove the default classes for the fields.
* Settings (under _Forms > Settings > Frontend Login_) include:
	* Roles to be blocked from the admin area
	* The page to redirect blocked users to
	* Whether or not to keep entries for each form.
		* NOTE: Always make sure the password field in any form includes the CSS class `password` - this will make sure password values are never stored in Gravity Forms entry data.
* Place the login form where you need them.
* Use the User Registration add-on for registration and profile updating.
