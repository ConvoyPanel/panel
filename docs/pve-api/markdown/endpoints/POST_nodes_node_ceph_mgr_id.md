# POST /nodes/{node}/ceph/mgr/{id}

Create Ceph Manager

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| id | string | no | The ID for the manager, when omitted the same as the nodename. |

## Request parameters

None.

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create Ceph Manager",
  "method": "POST",
  "name": "createmgr",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "default": "nodename",
        "description": "The ID for the manager, when omitted the same as the nodename.",
        "maxLength": 200,
        "optional": 1,
        "pattern": "[a-zA-Z0-9]([a-zA-Z0-9\\-]*[a-zA-Z0-9])?",
        "type": "string"
      },
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
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
