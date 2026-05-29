# POST /nodes/{node}/apt/update

This is used to resynchronize the package index files from their sources (apt-get update).

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| notify | boolean | no | Send notification about new packages. |
| quiet | boolean | no | Only produces output suitable for logging, omitting progress indicators. |

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
  "description": "This is used to resynchronize the package index files from their sources (apt-get update).",
  "method": "POST",
  "name": "update_database",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "notify": {
        "default": 0,
        "description": "Send notification about new packages.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "quiet": {
        "default": 0,
        "description": "Only produces output suitable for logging, omitting progress indicators.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
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
    "type": "string"
  }
}
```
