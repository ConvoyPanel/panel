# POST /nodes/{node}/storage/{storage}/upload

Upload templates, ISO images, OVAs and VM images.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| content | string | yes | Content type. |
| filename | string | yes | The name of the file to create. Caution: This will be normalized! |
| checksum | string | no | The expected checksum of the file. |
| checksum-algorithm | string | no | The algorithm to calculate the checksum of the file. |
| tmpfilename | string | no | The source file name. This parameter is usually set by the REST handler. You can only overwrite it when connecting to the trusted port on localhost. |

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
    "/storage/{storage}",
    [
      "Datastore.AllocateTemplate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Upload templates, ISO images, OVAs and VM images.",
  "method": "POST",
  "name": "upload",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "checksum": {
        "description": "The expected checksum of the file.",
        "optional": 1,
        "requires": "checksum-algorithm",
        "type": "string",
        "typetext": "<string>"
      },
      "checksum-algorithm": {
        "description": "The algorithm to calculate the checksum of the file.",
        "enum": [
          "md5",
          "sha1",
          "sha224",
          "sha256",
          "sha384",
          "sha512"
        ],
        "optional": 1,
        "requires": "checksum",
        "type": "string"
      },
      "content": {
        "description": "Content type.",
        "enum": [
          "iso",
          "vztmpl",
          "import"
        ],
        "format": "pve-storage-content",
        "type": "string"
      },
      "filename": {
        "description": "The name of the file to create. Caution: This will be normalized!",
        "maxLength": 255,
        "type": "string",
        "typetext": "<string>"
      },
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
      },
      "tmpfilename": {
        "description": "The source file name. This parameter is usually set by the REST handler. You can only overwrite it when connecting to the trusted port on localhost.",
        "optional": 1,
        "pattern": "/var/tmp/pveupload-[0-9a-f]+",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/storage/{storage}",
      [
        "Datastore.AllocateTemplate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "string"
  }
}
```
