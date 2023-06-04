# Soapbox Routes.

This module provides the user login/registration forms from the admin theme to the frontend users aswell by customising the user frontend routes to use the admin routes through a custom event subscriber.

To get this working, for the anonymous and authenticated user roles, the permission for viewing admin theme should be provided. This is already setup via an updb hook from this module and you can run the following drush commands to apply the update:

```
drush pm-enable soapbox_routes
drush cr
drush updb -y
```
