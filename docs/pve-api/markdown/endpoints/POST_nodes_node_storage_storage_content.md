# POST /nodes/{node}/storage/{storage}/content

Allocate disk images.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| filename | string | yes | The name of the file to create. |
| size | string | yes | Size in kilobyte (1024 bytes). Optional suffixes 'M' (megabyte, 1024K) and 'G' (gigabyte, 1024M) |
| vmid | integer | yes | Specify owner VM |
| format | string | no | Format of the image. |

## Returns

```json
{
  "description": "Volume identifier",
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/storage/{storage}",
    [
      "Datastore.AllocateSpace"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Allocate disk images.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "filename": {
        "description": "The name of the file to create.",
        "type": "string",
        "typetext": "<string>"
      },
      "format": {
        "description": "Format of the image.",
        "enum": [
          "raw",
          "qcow2",
          "subvol",
          "vmdk"
        ],
        "optional": 1,
        "requires": "size",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "size": {
        "description": "Size in kilobyte (1024 bytes). Optional suffixes 'M' (megabyte, 1024K) and 'G' (gigabyte, 1024M)",
        "pattern": "\\d+[MG]?",
        "type": "string"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "vmid": {
        "description": "Specify owner VM",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage/{storage}",
      [
        "Datastore.AllocateSpace"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "description": "Volume identifier",
    "type": "string"
  }
}
```
