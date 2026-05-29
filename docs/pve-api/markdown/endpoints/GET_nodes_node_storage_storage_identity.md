# GET /nodes/{node}/storage/{storage}/identity

Return identity information for this storage instance.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "id": {
      "description": "Unique identifier for this storage instance. The exact format and semantics depend on the storage plugin type.",
      "type": "string"
    },
    "type": {
      "description": "The type of the storage.",
      "enum": [
        "btrfs",
        "cephfs",
        "cifs",
        "dir",
        "esxi",
        "iscsi",
        "iscsidirect",
        "lvm",
        "lvmthin",
        "nfs",
        "pbs",
        "rbd",
        "zfs",
        "zfspool"
      ],
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
    "/storage/{storage}",
    [
      "Datastore.Audit",
      "Datastore.AllocateSpace"
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
  "description": "Return identity information for this storage instance.",
  "method": "GET",
  "name": "identity",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage/{storage}",
      [
        "Datastore.Audit",
        "Datastore.AllocateSpace"
      ],
      "any",
      1
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "id": {
        "description": "Unique identifier for this storage instance. The exact format and semantics depend on the storage plugin type.",
        "type": "string"
      },
      "type": {
        "description": "The type of the storage.",
        "enum": [
          "btrfs",
          "cephfs",
          "cifs",
          "dir",
          "esxi",
          "iscsi",
          "iscsidirect",
          "lvm",
          "lvmthin",
          "nfs",
          "pbs",
          "rbd",
          "zfs",
          "zfspool"
        ],
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
