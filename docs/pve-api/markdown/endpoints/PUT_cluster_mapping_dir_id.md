# PUT /cluster/mapping/dir/{id}

Update a directory mapping.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The ID of the directory mapping |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delete | string | no | A list of settings you want to delete. |
| description | string | no | Description of the directory mapping |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| map | array | no | A list of maps for the cluster nodes. |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/mapping/dir/{id}",
    [
      "Mapping.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Update a directory mapping.",
  "method": "PUT",
  "name": "update",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "description": {
        "description": "Description of the directory mapping",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "id": {
        "description": "The ID of the directory mapping",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      },
      "map": {
        "description": "A list of maps for the cluster nodes.",
        "items": {
          "format": {
            "node": {
              "description": "The cluster node name.",
              "format": "pve-node",
              "type": "string"
            },
            "path": {
              "description": "Absolute directory path that should be shared with the guest.",
              "format": "pve-storage-path-in-property-string",
              "type": "string"
            }
          },
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/mapping/dir/{id}",
      [
        "Mapping.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
