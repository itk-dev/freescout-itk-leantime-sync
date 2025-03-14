# ITK Leantime sync

Holds service used for connecting to Leantime.

## Setup

This plugin uses custom fields on users and on issues.
When the fields are present and have corresponding values Leantime issues are
updated when Freescout issues change status or assignee.

### User field:

- Name: ```Leantime user id```
- Type: ```Number```

### Issue field:

- Name: ```Leantime issue```
- Type: ```Number```

## Config

To map Freescout mailboxes to specific projects in Leantime this projects 
config.php file looks for .env variables starting with 
```LEANTIME_PROJECT_KEY_MAP```. i.e: ```LEANTIME_PROJECT_KEY_MAP_SUPPORT```
the suffix ```_SUPPORT``` is not used beyond labelling in .env file and could be anything.

Each mapping consists of a Freescout mailbox id and a Leantime project id seperated by comma:

```
### The project keys to add To-do's to (freescoutMailboxId, leantimeProjectId).
LEANTIME_PROJECT_KEY_MAP_SUPPORT=1,3
LEANTIME_PROJECT_KEY_MAP_DELTAG_AARHUS_FEEDBACK=2,4
```
