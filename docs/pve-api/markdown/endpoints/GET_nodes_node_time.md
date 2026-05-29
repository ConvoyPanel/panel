# GET /nodes/{node}/time

Read server time and time zone settings.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "localtime": {
      "description": "Seconds since 1970-01-01 00:00:00 (local time)",
      "minimum": 1297163644,
      "renderer": "timestamp_gmt",
      "type": "integer"
    },
    "time": {
      "description": "Seconds since 1970-01-01 00:00:00 UTC.",
      "minimum": 1297163644,
      "renderer": "timestamp",
      "type": "integer"
    },
    "timezone": {
      "description": "Time zone",
      "type": "string"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/nodes/{node}",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read server time and time zone settings.",
  "method": "GET",
  "name": "time",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
        "Sys.Audit"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "localtime": {
        "description": "Seconds since 1970-01-01 00:00:00 (local time)",
        "minimum": 1297163644,
        "renderer": "timestamp_gmt",
        "type": "integer"
      },
      "time": {
        "description": "Seconds since 1970-01-01 00:00:00 UTC.",
        "minimum": 1297163644,
        "renderer": "timestamp",
        "type": "integer"
      },
      "timezone": {
        "description": "Time zone",
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
