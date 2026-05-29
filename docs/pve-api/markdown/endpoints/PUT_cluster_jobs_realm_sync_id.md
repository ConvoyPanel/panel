# PUT /cluster/jobs/realm-sync/{id}

Update realm-sync job definition.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The ID of the job. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| schedule | string | yes | Backup schedule. The format is a subset of `systemd` calendar events. |
| comment | string | no | Description for the Job. |
| delete | string | no | A list of settings you want to delete. |
| enable-new | boolean | no | Enable newly synced users immediately. |
| enabled | boolean | no | Determines if the job is enabled. |
| remove-vanished | string | no | A semicolon-separated list of things to remove when they or the user vanishes during a sync. The following values are possible: 'entry' removes the user/group when not returned from the sync. 'properties' removes the set properties on existing user/group that do not appear in the source (even custom ones). 'acl' removes acls when the user/group is not returned from the sync. Instead of a list it also can be 'none' (the default). |
| scope | string | no | Select what to sync. |

## Returns

```json
{
  "type": "null"
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
  "description": "'Realm.AllocateUser' on '/access/realm/<realm>' and 'User.Modify' permissions to '/access/groups/'."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Update realm-sync job definition.",
  "method": "PUT",
  "name": "update_job",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "comment": {
        "description": "Description for the Job.",
        "maxLength": 512,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "enable-new": {
        "default": "1",
        "description": "Enable newly synced users immediately.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "enabled": {
        "default": 1,
        "description": "Determines if the job is enabled.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "id": {
        "description": "The ID of the job.",
        "format": "pve-configid",
        "maxLength": 64,
        "type": "string",
        "typetext": "<string>"
      },
      "remove-vanished": {
        "default": "none",
        "description": "A semicolon-separated list of things to remove when they or the user vanishes during a sync. The following values are possible: 'entry' removes the user/group when not returned from the sync. 'properties' removes the set properties on existing user/group that do not appear in the source (even custom ones). 'acl' removes acls when the user/group is not returned from the sync. Instead of a list it also can be 'none' (the default).",
        "optional": 1,
        "pattern": "(?:(?:(?:acl|properties|entry);)*(?:acl|properties|entry))|none",
        "type": "string",
        "typetext": "([acl];[properties];[entry])|none"
      },
      "schedule": {
        "description": "Backup schedule. The format is a subset of `systemd` calendar events.",
        "format": "pve-calendar-event",
        "maxLength": 128,
        "type": "string",
        "typetext": "<string>"
      },
      "scope": {
        "description": "Select what to sync.",
        "enum": [
          "users",
          "groups",
          "both"
        ],
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
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
    "description": "'Realm.AllocateUser' on '/access/realm/<realm>' and 'User.Modify' permissions to '/access/groups/'."
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
