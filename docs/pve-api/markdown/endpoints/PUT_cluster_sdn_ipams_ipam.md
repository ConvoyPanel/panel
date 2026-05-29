# PUT /cluster/sdn/ipams/{ipam}

Update sdn ipam object configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| ipam | string | yes | The SDN ipam object identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| delete | string | no | A list of settings you want to delete. |
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |
| fingerprint | string | no | Certificate SHA 256 fingerprint. |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| section | integer | no |  |
| token | string | no |  |
| url | string | no |  |

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
    "/sdn/ipams",
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
  "description": "Update sdn ipam object configuration.",
  "method": "PUT",
  "name": "update",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "delete": {
        "description": "A list of settings you want to delete.",
        "format": "pve-configid-list",
        "maxLength": 4096,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "fingerprint": {
        "description": "Certificate SHA 256 fingerprint.",
        "optional": 1,
        "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
        "type": "string"
      },
      "ipam": {
        "description": "The SDN ipam object identifier.",
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "section": {
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "token": {
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "url": {
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/ipams",
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
