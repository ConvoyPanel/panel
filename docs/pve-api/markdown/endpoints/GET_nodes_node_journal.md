# GET /nodes/{node}/journal

Read Journal

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| endcursor | string | no | End before the given Cursor. Conflicts with 'until' |
| lastentries | integer | no | Limit to the last X lines. Conflicts with a range. |
| since | integer | no | Display all log since this UNIX epoch. Conflicts with 'startcursor'. |
| startcursor | string | no | Start after the given Cursor. Conflicts with 'since' |
| until | integer | no | Display all log until this UNIX epoch. Conflicts with 'endcursor'. |

## Returns

```json
{
  "items": {
    "type": "string"
  },
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/nodes/{node}",
    [
      "Sys.Syslog"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read Journal",
  "download_allowed": 1,
  "method": "GET",
  "name": "journal",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "endcursor": {
        "description": "End before the given Cursor. Conflicts with 'until'",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "lastentries": {
        "description": "Limit to the last X lines. Conflicts with a range.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "since": {
        "description": "Display all log since this UNIX epoch. Conflicts with 'startcursor'.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "startcursor": {
        "description": "Start after the given Cursor. Conflicts with 'since'",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "until": {
        "description": "Display all log until this UNIX epoch. Conflicts with 'endcursor'.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Syslog"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "type": "string"
    },
    "type": "array"
  }
}
```
