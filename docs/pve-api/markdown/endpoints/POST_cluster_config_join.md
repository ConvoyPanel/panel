# POST /cluster/config/join

Joins this node into an existing cluster. If no links are given, default to IP resolved by node's hostname on single link (fallback fails for clusters with multiple links).

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| fingerprint | string | yes | Certificate SHA 256 fingerprint. |
| hostname | string | yes | Hostname (or IP) of an existing cluster member. |
| password | string | yes | Superuser (root) password of peer node. |
| force | boolean | no | Do not throw error if node already exists. |
| link[n] | string | no | Address and priority information of a single corosync link. (up to 8 links supported; link0..link7) |
| nodeid | integer | no | Node id for this node. |
| votes | integer | no | Number of votes for this node |

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
  "description": "Joins this node into an existing cluster. If no links are given, default to IP resolved by node's hostname on single link (fallback fails for clusters with multiple links).",
  "method": "POST",
  "name": "join",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "fingerprint": {
        "description": "Certificate SHA 256 fingerprint.",
        "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
        "type": "string"
      },
      "force": {
        "description": "Do not throw error if node already exists.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "hostname": {
        "description": "Hostname (or IP) of an existing cluster member.",
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
      "password": {
        "description": "Superuser (root) password of peer node.",
        "maxLength": 128,
        "type": "string",
        "typetext": "<string>"
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
    "type": "string"
  }
}
```
