# GET /nodes/{node}/firewall/log

Read firewall log

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| limit | integer | no |  |
| since | integer | no | Display log since this UNIX epoch. |
| start | integer | no |  |
| until | integer | no | Display log until this UNIX epoch. |

## Returns

```json
{
  "items": {
    "properties": {
      "n": {
        "description": "Line number",
        "type": "integer"
      },
      "t": {
        "description": "Line text",
        "type": "string"
      }
    },
    "type": "object"
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
  "description": "Read firewall log",
  "method": "GET",
  "name": "log",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "limit": {
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
        "description": "Display log since this UNIX epoch.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "start": {
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "until": {
        "description": "Display log until this UNIX epoch.",
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
      "properties": {
        "n": {
          "description": "Line number",
          "type": "integer"
        },
        "t": {
          "description": "Line text",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
