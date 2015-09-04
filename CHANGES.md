# Changelog for Pilau Gravity Forms Frontend Login

## 1.1.0 (????-??-??)
* Moved entry removal code to `after_submission()`, otherwise login redirect could bypass it
* Made sure password fields are never stored in entries (keys in on `password` in CSS class for field)
* Added `get_field_by_meta()`
* Added `delete_entry_data()`
* Added option to not redirect admin logins
* Rewritten to use bootstrap class

## 1.0.0 (2015-06-23)
* First version
