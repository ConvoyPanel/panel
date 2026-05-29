# POST /cluster/mapping/dir

Create a new directory mapping.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The ID of the directory mapping |
| map | array | yes | A list of maps for the cluster nodes. |
| description | string | no | Description of the directory mapping |

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
    "/mapping/dir",
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
  "description": "Create a new directory mapping.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "description": {
        "description": "Description of the directory mapping",
        "maxLength": 4096,
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
        "optional": 0,
        "type": "array",
        "typetext": "<array>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/mapping/dir",
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
