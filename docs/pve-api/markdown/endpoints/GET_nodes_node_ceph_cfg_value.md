# GET /nodes/{node}/ceph/cfg/value

Get configured values from either ceph.conf or the mon config DB. Underscores in section and key names are normalised to hyphens in the response, regardless of how they're written in the source.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| config-keys | string | yes | List of <section>:<config key> items separated by semicolon, comma or space. |

## Returns

```json
{
  "description": "Two-level map of {section} -> {key} -> value. Underscores in section and key names are normalised to hyphens.",
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
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
  "description": "Get configured values from either ceph.conf or the mon config DB. Underscores in section and key names are normalised to hyphens in the response, regardless of how they're written in the source.",
  "method": "GET",
  "name": "value",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "config-keys": {
        "description": "List of <section>:<config key> items separated by semicolon, comma or space.",
        "maxLength": 4096,
        "pattern": "(?^:^(?:(?^i:[0-9a-z\\-_\\.]+:[0-9a-zA-Z\\-_]+))(?:[;, ](?^i:[0-9a-z\\-_\\.]+:[0-9a-zA-Z\\-_]+))*$)",
        "type": "string",
        "typetext": "<section>:<config key>[;|,| <section>:<config key>]"
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
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "description": "Two-level map of {section} -> {key} -> value. Underscores in section and key names are normalised to hyphens.",
    "type": "object"
  }
}
```
