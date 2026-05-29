# GET /cluster/sdn/route-maps/entries/{route-map-id}

List all entries for a given Route Map

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| route-map-id | string | yes | The SDN route map identifier |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pending | boolean | no | Display pending config. |
| running | boolean | no | Display running config. |

## Returns

```json
{
  "items": {
    "properties": {
      "action": {
        "description": "Matching policy of a route map entry.",
        "enum": [
          "permit",
          "deny"
        ],
        "optional": 0,
        "type": "string"
      },
      "call": {
        "description": "The SDN route map identifier",
        "format": "pve-sdn-route-map-id",
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string"
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
        "type": "string"
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
        "type": "array"
      },
      "order": {
        "description": "The index of this route map entry",
        "maximum": 65535,
        "minimum": 0,
        "type": "integer"
      },
      "route-map-id": {
        "description": "The SDN route map identifier",
        "format": "pve-sdn-route-map-id",
        "type": "string"
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
        "type": "array"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "entry/{order}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/sdn/route-maps/{route-map-id}",
    [
      "SDN.Audit",
      "SDN.Allocate"
    ],
    "any",
    1
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List all entries for a given Route Map",
  "method": "GET",
  "name": "list_route_map_entries_for_route_map",
  "parameters": {
    "properties": {
      "pending": {
        "description": "Display pending config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "route-map-id": {
        "description": "The SDN route map identifier",
        "format": "pve-sdn-route-map-id",
        "type": "string",
        "typetext": "<string>"
      },
      "running": {
        "description": "Display running config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/route-maps/{route-map-id}",
      [
        "SDN.Audit",
        "SDN.Allocate"
      ],
      "any",
      1
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "action": {
          "description": "Matching policy of a route map entry.",
          "enum": [
            "permit",
            "deny"
          ],
          "optional": 0,
          "type": "string"
        },
        "call": {
          "description": "The SDN route map identifier",
          "format": "pve-sdn-route-map-id",
          "optional": 1,
          "type": "string"
        },
        "digest": {
          "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
          "maxLength": 64,
          "optional": 1,
          "type": "string"
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
          "type": "string"
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
          "type": "array"
        },
        "order": {
          "description": "The index of this route map entry",
          "maximum": 65535,
          "minimum": 0,
          "type": "integer"
        },
        "route-map-id": {
          "description": "The SDN route map identifier",
          "format": "pve-sdn-route-map-id",
          "type": "string"
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
          "type": "array"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "entry/{order}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
