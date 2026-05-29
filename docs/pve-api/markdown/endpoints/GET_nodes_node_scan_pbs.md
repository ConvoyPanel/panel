# GET /nodes/{node}/scan/pbs

Scan remote Proxmox Backup Server.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| password | string | yes | User password or API token secret. |
| server | string | yes | The server address (name or IP). |
| username | string | yes | User-name or API token-ID. |
| fingerprint | string | no | Certificate SHA 256 fingerprint. |
| port | integer | no | Optional port. |

## Returns

```json
{
  "items": {
    "properties": {
      "comment": {
        "description": "Comment from server.",
        "optional": 1,
        "type": "string"
      },
      "store": {
        "description": "The datastore name.",
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
    "/storage",
    [
      "Datastore.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Scan remote Proxmox Backup Server.",
  "method": "GET",
  "name": "pbsscan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "fingerprint": {
        "description": "Certificate SHA 256 fingerprint.",
        "optional": 1,
        "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "password": {
        "description": "User password or API token secret.",
        "type": "string",
        "typetext": "<string>"
      },
      "port": {
        "default": 8007,
        "description": "Optional port.",
        "maximum": 65535,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 65535)"
      },
      "server": {
        "description": "The server address (name or IP).",
        "format": "pve-storage-server",
        "type": "string",
        "typetext": "<string>"
      },
      "username": {
        "description": "User-name or API token-ID.",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage",
      [
        "Datastore.Allocate"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "comment": {
          "description": "Comment from server.",
          "optional": 1,
          "type": "string"
        },
        "store": {
          "description": "The datastore name.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
