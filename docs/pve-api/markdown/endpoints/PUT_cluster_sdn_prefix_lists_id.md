# PUT /cluster/sdn/prefix-lists/{id}

Update Prefix List

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The SDN prefix list identifier |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delete | array | no |  |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| entries | array | no |  |
| lock-token | string | no | the token for unlocking the global SDN configuration |

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
    "/sdn/prefix-lists/{id}",
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
  "description": "Update Prefix List",
  "method": "PUT",
  "name": "update_prefix_list",
  "parameters": {
    "properties": {
      "delete": {
        "items": {
          "enum": [
            "entries"
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
      "entries": {
        "items": {
          "format": {
            "action": {
              "enum": [
                "permit",
                "deny"
              ],
              "optional": 1,
              "type": "string"
            },
            "ge": {
              "maximum": 128,
              "minimum": 0,
              "optional": 1,
              "type": "integer"
            },
            "le": {
              "maximum": 128,
              "minimum": 0,
              "optional": 1,
              "type": "integer"
            },
            "prefix": {
              "format": "FullRangeCIDR",
              "optional": 1,
              "type": "string"
            },
            "seq": {
              "maximum": 4294967295,
              "minimum": 1,
              "optional": 1,
              "type": "integer"
            }
          },
          "type": "string"
        },
        "optional": 1,
        "type": "array",
        "typetext": "<array>"
      },
      "id": {
        "description": "The SDN prefix list identifier",
        "format": "pve-sdn-prefix-list-id",
        "type": "string",
        "typetext": "<string>"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/prefix-lists/{id}",
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
