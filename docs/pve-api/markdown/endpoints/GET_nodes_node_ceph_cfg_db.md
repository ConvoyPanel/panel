# GET /nodes/{node}/ceph/cfg/db

Get the Ceph configuration database.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "additionalProperties": 1,
    "properties": {
      "can_update_at_runtime": {
        "description": "Set if the value can be changed at runtime without restarting the affected daemons. Emitted as the integer 1/0 to match the existing PVE wire convention.",
        "type": "boolean"
      },
      "level": {
        "description": "Config level the entry is exposed at: 'basic' for operator-visible settings, 'advanced' for tuning parameters, 'dev' for developer-only knobs.",
        "enum": [
          "basic",
          "advanced",
          "dev"
        ],
        "type": "string"
      },
      "mask": {
        "description": "Match expression restricting the entry's scope; empty when the entry has no mask. Examples: 'host:foo', 'class:ssd'.",
        "type": "string"
      },
      "name": {
        "description": "Config key name.",
        "type": "string"
      },
      "section": {
        "description": "Ceph config section the entry applies to: 'global', a daemon type ('mon', 'osd', 'mgr', 'mds', 'client'), or a specific daemon (e.g. 'osd.0', 'mon.<name>').",
        "type": "string"
      },
      "value": {
        "description": "Configured value for the key (always serialised as a string by Ceph, regardless of the option's underlying type).",
        "type": "string"
      }
    },
    "type": "object"
  },
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
      "Sys.Audit",
      "Datastore.Audit"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get the Ceph configuration database.",
  "method": "GET",
  "name": "db",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit",
        "Datastore.Audit"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "additionalProperties": 1,
      "properties": {
        "can_update_at_runtime": {
          "description": "Set if the value can be changed at runtime without restarting the affected daemons. Emitted as the integer 1/0 to match the existing PVE wire convention.",
          "type": "boolean"
        },
        "level": {
          "description": "Config level the entry is exposed at: 'basic' for operator-visible settings, 'advanced' for tuning parameters, 'dev' for developer-only knobs.",
          "enum": [
            "basic",
            "advanced",
            "dev"
          ],
          "type": "string"
        },
        "mask": {
          "description": "Match expression restricting the entry's scope; empty when the entry has no mask. Examples: 'host:foo', 'class:ssd'.",
          "type": "string"
        },
        "name": {
          "description": "Config key name.",
          "type": "string"
        },
        "section": {
          "description": "Ceph config section the entry applies to: 'global', a daemon type ('mon', 'osd', 'mgr', 'mds', 'client'), or a specific daemon (e.g. 'osd.0', 'mon.<name>').",
          "type": "string"
        },
        "value": {
          "description": "Configured value for the key (always serialised as a string by Ceph, regardless of the option's underlying type).",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
