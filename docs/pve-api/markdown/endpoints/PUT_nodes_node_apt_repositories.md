# PUT /nodes/{node}/apt/repositories

Add a standard repository to the configuration

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| handle | string | yes | Handle that identifies a repository. |
| digest | string | no | Digest to detect modifications. |

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
    "/nodes/{node}",
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
  "description": "Add a standard repository to the configuration",
  "method": "PUT",
  "name": "add_repository",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "digest": {
        "description": "Digest to detect modifications.",
        "maxLength": 80,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "handle": {
        "description": "Handle that identifies a repository.",
        "type": "string",
        "typetext": "<string>"
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
      "/nodes/{node}",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "null"
  }
}
```
