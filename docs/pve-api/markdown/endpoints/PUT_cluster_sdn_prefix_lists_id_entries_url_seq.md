# PUT /cluster/sdn/prefix-lists/{id}/entries/{url_seq}

Update Prefix List Entry

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| action | string | no |  |
| delete | array | no |  |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| ge | integer | no |  |
| le | integer | no |  |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| prefix | string | no |  |
| seq | integer | no |  |

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
  "description": "Update Prefix List Entry",
  "method": "PUT",
  "name": "update_prefix_list_entry",
  "parameters": {
    "properties": {
      "action": {
        "enum": [
          "permit",
          "deny"
        ],
        "optional": 1,
        "type": "string"
      },
      "delete": {
        "items": {
          "enum": [
            "le",
            "ge",
            "seq"
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
      "ge": {
        "maximum": 128,
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - 128)"
      },
      "le": {
        "maximum": 128,
        "minimum": 0,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (0 - 128)"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "prefix": {
        "format": "FullRangeCIDR",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "seq": {
        "maximum": 4294967295,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 4294967295)"
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
