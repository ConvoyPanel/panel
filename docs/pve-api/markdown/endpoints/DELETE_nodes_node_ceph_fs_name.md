# DELETE /nodes/{node}/ceph/fs/{name}

Destroy a Ceph filesystem. Refuses if any PVE storage entry of type 'cephfs' still references the filesystem and is not disabled. Optionally also removes the storage entries and/or the underlying metadata and data pools.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | The Ceph filesystem name. |
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| remove-pools | boolean | no | Remove the metadata and data pools used by this filesystem. |
| remove-storages | boolean | no | Remove pveceph-managed storages configured for this filesystem. |

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
  "description": "Destroy a Ceph filesystem. Refuses if any PVE storage entry of type 'cephfs' still references the filesystem and is not disabled. Optionally also removes the storage entries and/or the underlying metadata and data pools.",
  "method": "DELETE",
  "name": "destroyfs",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "The Ceph filesystem name.",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "remove-pools": {
        "default": 0,
        "description": "Remove the metadata and data pools used by this filesystem.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "remove-storages": {
        "default": 0,
        "description": "Remove pveceph-managed storages configured for this filesystem.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
