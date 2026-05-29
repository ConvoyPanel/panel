# GET /nodes/{node}/scan/nfs

Scan remote NFS server.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| server | string | yes | The server address (name or IP). |

## Returns

```json
{
  "items": {
    "properties": {
      "options": {
        "description": "NFS export options.",
        "type": "string"
      },
      "path": {
        "description": "The exported path.",
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
  "description": "Scan remote NFS server.",
  "method": "GET",
  "name": "nfsscan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "server": {
        "description": "The server address (name or IP).",
        "format": "pve-storage-server",
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
        "options": {
          "description": "NFS export options.",
          "type": "string"
        },
        "path": {
          "description": "The exported path.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
