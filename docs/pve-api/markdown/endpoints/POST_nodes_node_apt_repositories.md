# POST /nodes/{node}/apt/repositories

Change the properties of a repository. Currently only allows enabling/disabling.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| index | integer | yes | Index within the file (starting from 0). |
| path | string | yes | Path to the containing file. |
| digest | string | no | Digest to detect modifications. |
| enabled | boolean | no | Whether the repository should be enabled or not. |

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
  "description": "Change the properties of a repository. Currently only allows enabling/disabling.",
  "method": "POST",
  "name": "change_repository",
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
      "enabled": {
        "description": "Whether the repository should be enabled or not.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "index": {
        "description": "Index within the file (starting from 0).",
        "type": "integer",
        "typetext": "<integer>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "path": {
        "description": "Path to the containing file.",
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
