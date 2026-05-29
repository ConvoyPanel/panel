# GET /cluster/sdn/dry-run

Dry-run the SDN apply action and return the difference between the current configuration and the pending configuration

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Returns

```json
{
  "properties": {
    "frr-diff": {
      "description": "The difference between the current and pending FRR configuration.",
      "optional": 1,
      "type": "string"
    },
    "interfaces-diff": {
      "description": "The difference between the current and pending /etc/network/interfaces.d/sdn configuration.",
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
  "description": "Dry-run the SDN apply action and return the difference between the current configuration and the pending configuration",
  "method": "GET",
  "name": "dry-run",
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
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "frr-diff": {
        "description": "The difference between the current and pending FRR configuration.",
        "optional": 1,
        "type": "string"
      },
      "interfaces-diff": {
        "description": "The difference between the current and pending /etc/network/interfaces.d/sdn configuration.",
        "optional": 1,
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
