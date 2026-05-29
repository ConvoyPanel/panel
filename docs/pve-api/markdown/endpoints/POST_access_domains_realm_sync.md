# POST /access/domains/{realm}/sync

Syncs users and/or groups from the configured LDAP to user.cfg. NOTE: Synced groups will have the name 'name-$realm', so make sure those groups do not exist to prevent overwriting.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| realm | string | yes | Authentication domain ID |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| enable-new | boolean | yes | Enable newly synced users immediately. |
| full | boolean | yes | DEPRECATED: use 'remove-vanished' instead. If set, uses the LDAP Directory as source of truth, deleting users or groups not returned from the sync and removing all locally modified properties of synced users. If not set, only syncs information which is present in the synced data, and does not delete or modify anything else. |
| purge | boolean | yes | DEPRECATED: use 'remove-vanished' instead. Remove ACLs for users or groups which were removed from the config during a sync. |
| remove-vanished | string | yes | A semicolon-separated list of things to remove when they or the user vanishes during a sync. The following values are possible: 'entry' removes the user/group when not returned from the sync. 'properties' removes the set properties on existing user/group that do not appear in the source (even custom ones). 'acl' removes acls when the user/group is not returned from the sync. Instead of a list it also can be 'none' (the default). |
| scope | string | yes | Select what to sync. |
| dry-run | boolean | no | If set, does not write anything. |

## Returns

```json
{
  "description": "Worker Task-UPID",
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "and",
    [
      "perm",
      "/access/realm/{realm}",
      [
        "Realm.AllocateUser"
      ]
    ],
    [
      "perm",
      "/access/groups",
      [
        "User.Modify"
      ]
    ]
  ],
  "description": "'Realm.AllocateUser' on '/access/realm/<realm>' and  'User.Modify' permissions to '/access/groups/'."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Syncs users and/or groups from the configured LDAP to user.cfg. NOTE: Synced groups will have the name 'name-$realm', so make sure those groups do not exist to prevent overwriting.",
  "method": "POST",
  "name": "sync",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "dry-run": {
        "default": 0,
        "description": "If set, does not write anything.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "enable-new": {
        "default": "1",
        "description": "Enable newly synced users immediately.",
        "optional": "1",
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "full": {
        "description": "DEPRECATED: use 'remove-vanished' instead. If set, uses the LDAP Directory as source of truth, deleting users or groups not returned from the sync and removing all locally modified properties of synced users. If not set, only syncs information which is present in the synced data, and does not delete or modify anything else.",
        "optional": "1",
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "purge": {
        "description": "DEPRECATED: use 'remove-vanished' instead. Remove ACLs for users or groups which were removed from the config during a sync.",
        "optional": "1",
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "realm": {
        "description": "Authentication domain ID",
        "format": "pve-realm",
        "maxLength": 32,
        "type": "string",
        "typetext": "<string>"
      },
      "remove-vanished": {
        "default": "none",
        "description": "A semicolon-separated list of things to remove when they or the user vanishes during a sync. The following values are possible: 'entry' removes the user/group when not returned from the sync. 'properties' removes the set properties on existing user/group that do not appear in the source (even custom ones). 'acl' removes acls when the user/group is not returned from the sync. Instead of a list it also can be 'none' (the default).",
        "optional": "1",
        "pattern": "(?:(?:(?:acl|properties|entry);)*(?:acl|properties|entry))|none",
        "type": "string",
        "typetext": "([acl];[properties];[entry])|none"
      },
      "scope": {
        "description": "Select what to sync.",
        "enum": [
          "users",
          "groups",
          "both"
        ],
        "optional": "1",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "and",
      [
        "perm",
        "/access/realm/{realm}",
        [
          "Realm.AllocateUser"
        ]
      ],
      [
        "perm",
        "/access/groups",
        [
          "User.Modify"
        ]
      ]
    ],
    "description": "'Realm.AllocateUser' on '/access/realm/<realm>' and  'User.Modify' permissions to '/access/groups/'."
  },
  "protected": 1,
  "returns": {
    "description": "Worker Task-UPID",
    "type": "string"
  }
}
```
