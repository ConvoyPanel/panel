# POST /nodes/{node}/ceph/fs/{name}

Create a Ceph filesystem

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| name | string | no | The ceph filesystem name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| add-storage | boolean | no | Configure the created CephFS as storage for this cluster. |
| pg_num | integer | no | Number of placement groups for the backing data pool. The metadata pool will use a quarter of this. |

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
  "description": "Create a Ceph filesystem",
  "method": "POST",
  "name": "createfs",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "add-storage": {
        "default": 0,
        "description": "Configure the created CephFS as storage for this cluster.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "name": {
        "default": "cephfs",
        "description": "The ceph filesystem name.",
        "optional": 1,
        "pattern": "(?^:^[^:/\\s]+$)",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "pg_num": {
        "default": 128,
        "description": "Number of placement groups for the backing data pool. The metadata pool will use a quarter of this.",
        "maximum": 32768,
        "minimum": 8,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (8 - 32768)"
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
