# GET /nodes/{node}/ceph/cmd-safety

Heuristical check if it is safe to perform an action.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| action | string | yes | Action to check |
| id | string | yes | ID of the service |
| service | string | yes | Service type |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "safe": {
      "description": "True if Ceph reports the requested action is safe.",
      "type": "boolean"
    },
    "status": {
      "description": "Human-readable status message from Ceph (typically the reason an action is not safe); absent when Ceph returned no message.",
      "optional": 1,
      "type": "string"
    }
  },
  "type": "object"
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
  "description": "Heuristical check if it is safe to perform an action.",
  "method": "GET",
  "name": "cmd_safety",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "action": {
        "description": "Action to check",
        "enum": [
          "stop",
          "destroy"
        ],
        "type": "string"
      },
      "id": {
        "description": "ID of the service",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "service": {
        "description": "Service type",
        "enum": [
          "osd",
          "mon",
          "mds"
        ],
        "type": "string"
      }
    }
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
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "safe": {
        "description": "True if Ceph reports the requested action is safe.",
        "type": "boolean"
      },
      "status": {
        "description": "Human-readable status message from Ceph (typically the reason an action is not safe); absent when Ceph returned no message.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
