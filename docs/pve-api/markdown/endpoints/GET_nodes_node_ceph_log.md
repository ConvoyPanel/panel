# GET /nodes/{node}/ceph/log

Read ceph log

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| limit | integer | no | Maximum number of log lines to return. Defaults to the dump_logfile limit (typically 50) when omitted. |
| start | integer | no | Offset of the first log line to return (0-based). |

## Returns

```json
{
  "items": {
    "properties": {
      "n": {
        "description": "Log-file line number (1-based).",
        "type": "integer"
      },
      "t": {
        "description": "Log line text.",
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
  "description": "Read ceph log",
  "method": "GET",
  "name": "log",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "limit": {
        "description": "Maximum number of log lines to return. Defaults to the dump_logfile limit (typically 50) when omitted.",
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
      "start": {
        "description": "Offset of the first log line to return (0-based).",
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
          "description": "Log-file line number (1-based).",
          "type": "integer"
        },
        "t": {
          "description": "Log line text.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
