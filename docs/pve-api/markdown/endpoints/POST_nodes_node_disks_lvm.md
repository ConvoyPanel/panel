# POST /nodes/{node}/disks/lvm

Create an LVM Volume Group

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| device | string | yes | The block device you want to create the volume group on |
| name | string | yes | The storage identifier. |
| add_storage | boolean | no | Configure storage using the Volume Group |

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
  ],
  "description": "Requires additionally 'Datastore.Allocate' on /storage when setting 'add_storage'"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Create an LVM Volume Group",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "add_storage": {
        "default": 0,
        "description": "Configure storage using the Volume Group",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "device": {
        "description": "The block device you want to create the volume group on",
        "type": "string",
        "typetext": "<string>"
      },
      "name": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
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
    ],
    "description": "Requires additionally 'Datastore.Allocate' on /storage when setting 'add_storage'"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
