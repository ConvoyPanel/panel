# GET /nodes/{node}/apt/changelog

Get package changelogs.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | Package name. |
| version | string | no | Package version. |

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
  "description": "Get package changelogs.",
  "method": "GET",
  "name": "changelog",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "Package name.",
        "pattern": "(?^:[a-z0-9][-+.a-z0-9:]+)",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "version": {
        "description": "Package version.",
        "optional": 1,
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
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
