# PUT /cluster/sdn/route-maps/entries/{route-map-id}/entry/{order}

Update Route Map Entry

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| order | integer | yes | The index of this route map entry |
| route-map-id | string | yes | The SDN route map identifier |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| action | string | no | Matching policy of a route map entry. |
| call | string | no | The SDN route map identifier |
| delete | array | no |  |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| exit-action | string | no |  |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| match | array | no |  |
| set | array | no |  |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/sdn/route-maps/{route-map-id}",
    [
      "SDN.Allocate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Update Route Map Entry",
  "method": "PUT",
  "name": "update_route_map_entry",
  "parameters": {
    "properties": {
      "action": {
        "description": "Matching policy of a route map entry.",
        "enum": [
          "permit",
          "deny"
        ],
        "optional": 1,
        "type": "string"
      },
      "call": {
        "description": "The SDN route map identifier",
        "format": "pve-sdn-route-map-id",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "delete": {
        "items": {
          "enum": [
            "set",
            "match",
            "call",
            "exit-action"
          ],
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "exit-action": {
        "format": {
          "key": {
            "enum": [
              "on-match-goto",
              "on-match-next",
              "continue"
            ],
            "type": "string"
          },
          "value": {
            "description": "The index of this route map entry",
            "maximum": 65535,
            "minimum": 0,
            "optional": 1,
            "type": "integer"
          }
        },
        "optional": 1,
        "type": "string",
        "typetext": "key=<on-match-goto|on-match-next|continue> [,value=<integer>]"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "match": {
        "items": {
          "format": {
            "key": {
              "enum": [
                "route-type",
                "vni",
                "ip-address-prefix-list",
                "ip6-address-prefix-list",
                "ip-next-hop-prefix-list",
                "ip6-next-hop-prefix-list",
                "ip-next-hop-address",
                "ip6-next-hop-address",
                "metric",
                "local-preference",
                "peer",
                "tag"
              ],
              "type": "string"
            },
            "value": {
              "description": "Value that the field <key> should be matched on.",
              "format_description": "<key-dependent>",
              "optional": 1,
              "type": "string"
            }
          },
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "order": {
        "description": "The index of this route map entry",
        "maximum": 65535,
        "minimum": 0,
        "type": "integer",
        "typetext": "<integer> (0 - 65535)"
      },
      "route-map-id": {
        "description": "The SDN route map identifier",
        "format": "pve-sdn-route-map-id",
        "type": "string",
        "typetext": "<string>"
      },
      "set": {
        "items": {
          "format": {
            "key": {
              "enum": [
                "ip-next-hop-peer-address",
                "ip-next-hop",
                "ip-next-hop-unchanged",
                "ip6-next-hop-peer-address",
                "ip6-next-hop-prefer-global",
                "ip6-next-hop",
                "local-preference",
                "tag",
                "weight",
                "metric",
                "src"
              ],
              "type": "string"
            },
            "value": {
              "description": "Value that the field <key> should be set to.",
              "format_description": "<key-dependent>",
              "optional": 1,
              "type": "string"
            }
          },
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/route-maps/{route-map-id}",
      [
        "SDN.Allocate"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
