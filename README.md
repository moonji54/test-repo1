# Installation instructions

[See Wiki home](https://gitlab.com/sb-dev-team/white-label-drupal-9/-/wikis/Home)

# Connect to Staging environment - WIP

[See instructions to setup your 1password public key](https://docs.google.com/document/d/1-zus9COVvHZsmavxAgZdaA3fEfybOlqRGl1Y5ZkNlYs/edit#heading=h.xzgpjcaienuq)

1. Make sure your public key has been shared to the hosting provider (this should be already done for the SBX dev team)
2. Add the configuration to `.ssh/config`:

```apacheconf
#### NRGI STAGING ###
Host nrgi-staging-cloudelligent
   Hostname 52.202.177.255
   User ubuntu
   Port 22
   IdentityAgent ~/.1password/agent.sock
```

3. Connect to the server (Two-steps connection):

- Connect to the 'bastion' server, run: `ssh nrgi-staging-cloudelligent`
- Connect to the staging server (after connecting and 'inside' the 'bastion server' above),
  run: `ssh -i "nrgi_dev" nrgi_dev@10.3.4.84` 

