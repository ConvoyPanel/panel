# GET /cluster/config/join

Get information needed to join this cluster over the connected node.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | no | The node for which the joinee gets the nodeinfo. |

## Returns

```json
{
  "additionalProperties": 0,
  "properties": {
    "config_digest": {
      "type": "string"
    },
    "nodelist": {
      "items": {
        "additionalProperties": 1,
        "properties": {
          "name": {
            "description": "The cluster node name.",
            "format": "pve-node",
            "type": "string"
          },
          "nodeid": {
            "description": "Node id for this node.",
            "minimum": 1,
            "optional": 1,
            "type": "integer"
          },
          "pve_addr": {
            "format": "ip",
            "type": "string"
          },
          "pve_fp": {
            "description": "Certificate SHA 256 fingerprint.",
            "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
            "type": "string"
          },
          "quorum_votes": {
            "minimum": 0,
            "type": "integer"
          },
          "ring0_addr": {
            "description": "Address and priority information of a single corosync link. (up to 8 links supported; link0..link7)",
            "format": {
              "address": {
                "default_key": 1,
                "description": "Hostname (or IP) of this corosync link address.",
                "format": "address",
                "format_description": "IP",
                "type": "string"
              },
              "priority": {
                "default": 0,
                "description": "The priority for the link when knet is used in 'passive' mode (default). Lower value means higher priority. Only valid for cluster create, ignored on node add.",
                "maximum": 255,
                "minimum": 0,
                "optional": 1,
                "type": "integer"
              }
            },
            "optional": 1,
            "type": "string"
          }
        },
        "type": "object"
      },
      "type": "array"
    },
    "preferred_node": {
      "description": "The cluster node name.",
      "format": "pve-node",
      "type": "string"
    },
    "totem": {
      "type": "object"
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
  "description": "Get information needed to join this cluster over the connected node.",
  "method": "GET",
  "name": "join_info",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "default": "current connected node",
        "description": "The node for which the joinee gets the nodeinfo. ",
        "format": "pve-node",
        "optional": 1,
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
  "returns": {
    "additionalProperties": 0,
    "properties": {
      "config_digest": {
        "type": "string"
      },
      "nodelist": {
        "items": {
          "additionalProperties": 1,
          "properties": {
            "name": {
              "description": "The cluster node name.",
              "format": "pve-node",
              "type": "string"
            },
            "nodeid": {
              "description": "Node id for this node.",
              "minimum": 1,
              "optional": 1,
              "type": "integer"
            },
            "pve_addr": {
              "format": "ip",
              "type": "string"
            },
            "pve_fp": {
              "description": "Certificate SHA 256 fingerprint.",
              "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
              "type": "string"
            },
            "quorum_votes": {
              "minimum": 0,
              "type": "integer"
            },
            "ring0_addr": {
              "description": "Address and priority information of a single corosync link. (up to 8 links supported; link0..link7)",
              "format": {
                "address": {
                  "default_key": 1,
                  "description": "Hostname (or IP) of this corosync link address.",
                  "format": "address",
                  "format_description": "IP",
                  "type": "string"
                },
                "priority": {
                  "default": 0,
                  "description": "The priority for the link when knet is used in 'passive' mode (default). Lower value means higher priority. Only valid for cluster create, ignored on node add.",
                  "maximum": 255,
                  "minimum": 0,
                  "optional": 1,
                  "type": "integer"
                }
              },
              "optional": 1,
              "type": "string"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "preferred_node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string"
      },
      "totem": {
        "type": "object"
      }
    },
    "type": "object"
  }
}
```
