# Soapbox Drupal Modules Wiki

Check out the [WIKI page](https://gitlab.com/sb-dev-team/soapbox-drupal-modules/-/wikis/home).

# Pushing to Soapbox Drupal Modules UNU

The `unu-main` branch gets pushed to https://bitbucket.org/soapbox_support/soapbox-drupal-modules-unu/ `master` branch.

### Once:

This adds the bitbucket Soapbox repo as a remote.
```
git remote add soapbox-drupal-modules-unu git@bitbucket.org:soapbox_support/soapbox-drupal-modules-unu.git
```

This adds the bitbucket UNU repo as a remote.
```
git remote add unu git@bitbucket.org:unuwebdev/soapbox-drupal-modules.git
```

### Every time you wish to push:

This pushes unu-main branch to the soapbox bitbucket master branch.
```
git checkout unu-main
git push soapbox-drupal-modules-unu unu-main:master
```

This pushes unu-main branch to the unu bitbucket master branch.
```
git checkout unu-main
git push unu unu-main:master
```

# Pushing to Soapbox Drupal Modules CLEAR

The `clear-main` branch gets pushed to https://gitlab.com/sb-dev-team/soapbox-drupal-modules-clear `master`  repo.

### Once:

This adds the soapbox-drupal-modules-clear CLEAR repo as a remote.
```
git remote add soapbox-drupal-modules-clear git@gitkab.com:sb-dev-team/soapbox-drupal-modules-clear
```


### Every time you wish to push:

This pushes clear-main branch to the soapbox-drupal-modules-clear master branch.
```
git checkout clear-main
git push soapbox-drupal-modules-clear clear-main:master
```
