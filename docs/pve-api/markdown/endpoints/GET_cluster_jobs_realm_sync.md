# GET /cluster/jobs/realm-sync

List configured realm-sync-jobs.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "comment": {
        "description": "A comment for the job.",
        "optional": 1,
        "type": "string"
      },
      "enabled": {
        "description": "If the job is enabled or not.",
        "type": "boolean"
      },
      "id": {
        "description": "The ID of the entry.",
        "type": "string"
      },
      "last-run": {
        "description": "Last execution time of the job in seconds since the beginning of the UNIX epoch",
        "optional": 1,
        "type": "integer"
      },
      "next-run": {
        "description": "Next planned execution time of the job in seconds since the beginning of the UNIX epoch.",
        "optional": 1,
        "type": "integer"
      },
      "realm": {
        "description": "Authentication domain ID",
        "format": "pve-realm",
        "maxLength": 32,
        "type": "string"
      },
      "remove-vanished": {
        "default": "none",
        "description": "A semicolon-separated list of things to remove when they or the user vanishes during a sync. The following values are possible: 'entry' removes the user/group when not returned from the sync. 'properties' removes the set properties on existing user/group that do not appear in the source (even custom ones). 'acl' removes acls when the user/group is not returned from the sync. Instead of a list it also can be 'none' (the default).",
        "optional": "1",
        "pattern": "(?:(?:(?:acl|properties|entry);)*(?:acl|properties|entry))|none",
        "type": "string",
        "typetext": "([acl];[properties];[entry])|none"
      },
      "schedule": {
        "description": "The configured sync schedule.",
        "type": "string"
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
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{id}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List configured realm-sync-jobs.",
  "method": "GET",
  "name": "syncjob_index",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "comment": {
          "description": "A comment for the job.",
          "optional": 1,
          "type": "string"
        },
        "enabled": {
          "description": "If the job is enabled or not.",
          "type": "boolean"
        },
        "id": {
          "description": "The ID of the entry.",
          "type": "string"
        },
        "last-run": {
          "description": "Last execution time of the job in seconds since the beginning of the UNIX epoch",
          "optional": 1,
          "type": "integer"
        },
        "next-run": {
          "description": "Next planned execution time of the job in seconds since the beginning of the UNIX epoch.",
          "optional": 1,
          "type": "integer"
        },
        "realm": {
          "description": "Authentication domain ID",
          "format": "pve-realm",
          "maxLength": 32,
          "type": "string"
        },
        "remove-vanished": {
          "default": "none",
          "description": "A semicolon-separated list of things to remove when they or the user vanishes during a sync. The following values are possible: 'entry' removes the user/group when not returned from the sync. 'properties' removes the set properties on existing user/group that do not appear in the source (even custom ones). 'acl' removes acls when the user/group is not returned from the sync. Instead of a list it also can be 'none' (the default).",
          "optional": "1",
          "pattern": "(?:(?:(?:acl|properties|entry);)*(?:acl|properties|entry))|none",
          "type": "string",
          "typetext": "([acl];[properties];[entry])|none"
        },
        "schedule": {
          "description": "The configured sync schedule.",
          "type": "string"
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
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{id}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
