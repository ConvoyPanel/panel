# POST /cluster/config

Generate new cluster configuration. If no links given, default to local IP address as link0.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| clustername | string | yes | The name of the cluster. |
| link[n] | string | no | Address and priority information of a single corosync link. (up to 8 links supported; link0..link7) |
| nodeid | integer | no | Node id for this node. |
| token-coefficient | integer | no | Coefficient used to determine Corosync's token timeout. See the corosync.conf(5) manual for more details. |
| votes | integer | no | Number of votes for this node. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

Not specified.

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Generate new cluster configuration. If no links given, default to local IP address as link0.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "clustername": {
        "description": "The name of the cluster.",
        "format": "pve-node",
        "maxLength": 15,
        "type": "string",
        "typetext": "<string>"
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
      "nodeid": {
        "description": "Node id for this node.",
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - N)"
      },
      "token-coefficient": {
        "default": 125,
        "description": "Coefficient used to determine Corosync's token timeout. See the corosync.conf(5) manual for more details.",
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - N)"
      },
      "votes": {
        "description": "Number of votes for this node.",
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - N)"
      }
    }
  },
  "protected": 1,
  "returns": {
    "type": "string"
  }
}
```
