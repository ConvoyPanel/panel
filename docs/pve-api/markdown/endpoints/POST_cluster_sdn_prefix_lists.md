# POST /cluster/sdn/prefix-lists

Create Prefix List

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The SDN prefix list identifier |
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
    "/sdn/prefix-lists",
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
  "description": "Create Prefix List",
  "method": "POST",
  "name": "create_prefix_list_entry",
  "parameters": {
    "properties": {
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
              "optional": 0,
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
              "optional": 0,
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
      "/sdn/prefix-lists",
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
