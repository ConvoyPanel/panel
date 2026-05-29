# GET /storage

Storage index.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | Only list storage of specific type |

## Returns

```json
{
  "items": {
    "properties": {
      "storage": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{storage}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'Datastore.Audit' or 'Datastore.AllocateSpace' permissions on '/storage/<storage>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Storage index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "type": {
        "description": "Only list storage of specific type",
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
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Only list entries where you have 'Datastore.Audit' or 'Datastore.AllocateSpace' permissions on '/storage/<storage>'",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "storage": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{storage}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
