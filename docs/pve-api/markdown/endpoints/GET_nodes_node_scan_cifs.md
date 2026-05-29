# GET /nodes/{node}/scan/cifs

Scan remote CIFS server.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| server | string | yes | The server address (name or IP). |
| domain | string | no | SMB domain (Workgroup). |
| password | string | no | User password. |
| username | string | no | User name. |

## Returns

```json
{
  "items": {
    "properties": {
      "description": {
        "description": "Descriptive text from server.",
        "type": "string"
      },
      "share": {
        "description": "The cifs share name.",
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
  "description": "Scan remote CIFS server.",
  "method": "GET",
  "name": "cifsscan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "domain": {
        "description": "SMB domain (Workgroup).",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "password": {
        "description": "User password.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "server": {
        "description": "The server address (name or IP).",
        "format": "pve-storage-server",
        "type": "string",
        "typetext": "<string>"
      },
      "username": {
        "description": "User name.",
        "optional": 1,
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
        "description": {
          "description": "Descriptive text from server.",
          "type": "string"
        },
        "share": {
          "description": "The cifs share name.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
