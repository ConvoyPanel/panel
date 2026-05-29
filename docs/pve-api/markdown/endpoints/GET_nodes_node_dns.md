# GET /nodes/{node}/dns

Read DNS settings.

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
    "dns1": {
      "description": "First name server IP address.",
      "optional": 1,
      "type": "string"
    },
    "dns2": {
      "description": "Second name server IP address.",
      "optional": 1,
      "type": "string"
    },
    "dns3": {
      "description": "Third name server IP address.",
      "optional": 1,
      "type": "string"
    },
    "search": {
      "description": "Search domain for host-name lookup.",
      "optional": 1,
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
  "description": "Read DNS settings.",
  "method": "GET",
  "name": "dns",
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
      "dns1": {
        "description": "First name server IP address.",
        "optional": 1,
        "type": "string"
      },
      "dns2": {
        "description": "Second name server IP address.",
        "optional": 1,
        "type": "string"
      },
      "dns3": {
        "description": "Third name server IP address.",
        "optional": 1,
        "type": "string"
      },
      "search": {
        "description": "Search domain for host-name lookup.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
