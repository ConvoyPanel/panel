# GET /nodes/{node}/scan/iscsi

Scan remote iSCSI server.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| portal | string | yes | The iSCSI portal (IP or DNS name with optional port). |

## Returns

```json
{
  "items": {
    "properties": {
      "portal": {
        "description": "The iSCSI portal name.",
        "type": "string"
      },
      "target": {
        "description": "The iSCSI target name.",
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
  "description": "Scan remote iSCSI server.",
  "method": "GET",
  "name": "iscsiscan",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "portal": {
        "description": "The iSCSI portal (IP or DNS name with optional port).",
        "format": "pve-storage-portal-dns",
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
        "portal": {
          "description": "The iSCSI portal name.",
          "type": "string"
        },
        "target": {
          "description": "The iSCSI target name.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
