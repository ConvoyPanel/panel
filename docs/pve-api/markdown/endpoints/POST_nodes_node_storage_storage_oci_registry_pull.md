# POST /nodes/{node}/storage/{storage}/oci-registry-pull

Pull an OCI image from a registry.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| reference | string | yes | The reference to the OCI image to download. |
| filename | string | no | Custom destination file name of the OCI image. Caution: This will be normalized! |

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
      "perm",
      "/nodes/{node}",
      [
        "Sys.AccessNetwork"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Pull an OCI image from a registry.",
  "method": "POST",
  "name": "oci_registry_pull",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "filename": {
        "description": "Custom destination file name of the OCI image. Caution: This will be normalized!",
        "maxLength": 255,
        "minLength": 1,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "reference": {
        "description": "The reference to the OCI image to download.",
        "pattern": "^(?:(?:[a-zA-Z\\d]|[a-zA-Z\\d][a-zA-Z\\d-]*[a-zA-Z\\d])(?:\\.(?:[a-zA-Z\\d]|[a-zA-Z\\d][a-zA-Z\\d-]*[a-zA-Z\\d]))*(?::\\d+)?/)?[a-z\\d]+(?:(?:[._]|__|[-]*)[a-z\\d]+)*(?:/[a-z\\d]+(?:(?:[._]|__|[-]*)[a-z\\d]+)*)*:\\w[\\w.-]{0,127}$",
        "type": "string"
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
      "and",
      [
        "perm",
        "/storage/{storage}",
        [
          "Datastore.AllocateTemplate"
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
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
