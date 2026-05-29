# GET /nodes/{node}/syslog

Read system log

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| limit | integer | no |  |
| service | string | no | Service ID |
| since | string | no | Display all log since this date-time string. |
| start | integer | no |  |
| until | string | no | Display all log until this date-time string. |

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
  "description": "Read system log",
  "method": "GET",
  "name": "syslog",
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
      "service": {
        "description": "Service ID",
        "maxLength": 128,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "since": {
        "description": "Display all log since this date-time string.",
        "optional": 1,
        "pattern": "^\\d{4}-\\d{2}-\\d{2}( \\d{2}:\\d{2}(:\\d{2})?)?$",
        "type": "string"
      },
      "start": {
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "until": {
        "description": "Display all log until this date-time string.",
        "optional": 1,
        "pattern": "^\\d{4}-\\d{2}-\\d{2}( \\d{2}:\\d{2}(:\\d{2})?)?$",
        "type": "string"
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
