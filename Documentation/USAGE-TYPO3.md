[<-- Back to main section](../README.md)

# Usage `ct typo3:...`

## Backend user injection

The `ct typo3:beuser` can be used for creating a backend user in all or a specific TYPO3 database 
(with salted password support).

Default username: dev
Default password: dev

```bash
# Create the user in all databases
ct typo3:beuser

# Create the user in typo3 databases
ct typo3:beuser typo3

# Create the user in typo3 databases with plain password (no salted password)
ct typo3:beuser typo3 --plain
```

## Automatic domain manipulation

The `ct typo3:domain` can be used for manipulation of the domain records eg. for matching your
development environment.

```bash
# Add a .vm at the and of all domains in all databases
ct typo3:domain

# Add a .vm at the and of all domains in typo3 database
ct typo3:domain typo3

# Add a .vm at the and of all domains and remove all *.vagrantshare.com domains (used by vagrant:share)
ct typo3:domain --remove='*.vagrantshare.com'

# Add a .vm at the and of all domains and duplicate all domains with the suffix .vagrantshare.com (used by vagrant:share)
ct typo3:domain --duplicate='.vagrantshare.com'
```


