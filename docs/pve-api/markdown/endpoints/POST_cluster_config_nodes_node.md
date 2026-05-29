# POST /cluster/config/nodes/{node}

Adds a node to the cluster configuration. This call is for internal use.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| apiversion | integer | no | The JOIN_API_VERSION of the new node. |
| force | boolean | no | Do not throw error if node already exists. |
| link[n] | string | no | Address and priority information of a single corosync link. (up to 8 links supported; link0..link7) |
| new_node_ip | string | no | IP Address of node to add. Used as fallback if no links are given. |
| nodeid | integer | no | Node id for this node. |
| votes | integer | no | Number of votes for this node |

## Returns

```json
{
  "properties": {
    "corosync_authkey": {
      "type": "string"
    },
    "corosync_conf": {
      "type": "string"
    },
    "warnings": {
      "items": {
        "type": "string"
      },
      "type": "array"
    }
  },
  "type": "object"
}
```

## Permissions

Not specified.

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Adds a node to the cluster configuration. This call is for internal use.",
  "method": "POST",
  "name": "addnode",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "apiversion": {
        "description": "The JOIN_API_VERSION of the new node.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "force": {
        "description": "Do not throw error if node already exists.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "link[n]": {
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
        "type": "string",
        "typetext": "[address=]<IP> [,priority=<integer>]"
      },
      "new_node_ip": {
        "description": "IP Address of node to add. Used as fallback if no links are given.",
        "format": "ip",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "nodeid": {
        "description": "Node id for this node.",
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - N)"
      },
      "votes": {
        "description": "Number of votes for this node",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      }
    }
  },
  "protected": 1,
  "returns": {
    "properties": {
      "corosync_authkey": {
        "type": "string"
      },
      "corosync_conf": {
        "type": "string"
      },
      "warnings": {
        "items": {
          "type": "string"
        },
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
