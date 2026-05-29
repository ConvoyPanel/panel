# POST /cluster/sdn/prefix-lists/{id}/entries

Create Prefix List Entry

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The SDN prefix list identifier |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| action | string | yes |  |
| prefix | string | yes |  |
| ge | integer | no |  |
| le | integer | no |  |
| lock-token | string | no | the token for unlocking the global SDN configuration |
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
  "description": "Create Prefix List Entry",
  "method": "POST",
  "name": "create_prefix_list_entry",
  "parameters": {
    "properties": {
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
        "type": "integer",
        "typetext": "<integer> (0 - 128)"
      },
      "id": {
        "description": "The SDN prefix list identifier",
        "format": "pve-sdn-prefix-list-id",
        "type": "string",
        "typetext": "<string>"
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
        "optional": 0,
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
