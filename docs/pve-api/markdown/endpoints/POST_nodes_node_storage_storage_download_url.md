# POST /nodes/{node}/storage/{storage}/download-url

Download templates, ISO images, OVAs and VM images by using an URL.

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
| url | string | yes | The URL to download the file from. |
| checksum | string | no | The expected checksum of the file. |
| checksum-algorithm | string | no | The algorithm to calculate the checksum of the file. |
| compression | string | no | Decompress the downloaded file using the specified compression algorithm. |
| verify-certificates | boolean | no | If false, no SSL/TLS certificates will be verified. |

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
    "and",
    [
      "perm",
      "/storage/{storage}",
      [
        "Datastore.AllocateTemplate"
      ]
    ],
    [
      "or",
      [
        "perm",
        "/",
        [
          "Sys.Audit",
          "Sys.Modify"
        ]
      ],
      [
        "perm",
        "/nodes/{node}",
        [
          "Sys.AccessNetwork"
        ]
      ]
    ]
  ],
  "description": "Requires allocation access on the storage and as this allows one to probe the (local!) host network indirectly it also requires one of Sys.Modify on / (for backwards compatibility) or the newer Sys.AccessNetwork privilege on the node."
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Download templates, ISO images, OVAs and VM images by using an URL.",
  "method": "POST",
  "name": "download_url",
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
      "compression": {
        "description": "Decompress the downloaded file using the specified compression algorithm.",
        "enum": null,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
      "url": {
        "description": "The URL to download the file from.",
        "pattern": "https?://.*",
        "type": "string"
      },
      "verify-certificates": {
        "default": 1,
        "description": "If false, no SSL/TLS certificates will be verified.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "and",
      [
        "perm",
        "/storage/{storage}",
        [
          "Datastore.AllocateTemplate"
        ]
      ],
      [
        "or",
        [
          "perm",
          "/",
          [
            "Sys.Audit",
            "Sys.Modify"
          ]
        ],
        [
          "perm",
          "/nodes/{node}",
          [
            "Sys.AccessNetwork"
          ]
        ]
      ]
    ],
    "description": "Requires allocation access on the storage and as this allows one to probe the (local!) host network indirectly it also requires one of Sys.Modify on / (for backwards compatibility) or the newer Sys.AccessNetwork privilege on the node."
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
