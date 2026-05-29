# POST /cluster/sdn/dns

Create a new sdn dns object.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| dns | string | yes | The SDN dns object identifier. |
| key | string | yes |  |
| type | string | yes | Plugin type. |
| url | string | yes |  |
| fingerprint | string | no | Certificate SHA 256 fingerprint. |
| lock-token | string | no | the token for unlocking the global SDN configuration |
| reversemaskv6 | integer | no |  |
| reversev6mask | integer | no |  |
| ttl | integer | no |  |

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
    "/sdn/dns",
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
  "description": "Create a new sdn dns object.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "dns": {
        "description": "The SDN dns object identifier.",
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      },
      "fingerprint": {
        "description": "Certificate SHA 256 fingerprint.",
        "optional": 1,
        "pattern": "([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}",
        "type": "string"
      },
      "key": {
        "optional": 0,
        "type": "string",
        "typetext": "<string>"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "reversemaskv6": {
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "reversev6mask": {
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "ttl": {
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "type": {
        "description": "Plugin type.",
        "enum": [
          "powerdns"
        ],
        "format": "pve-configid",
        "type": "string"
      },
      "url": {
        "optional": 0,
        "type": "string",
        "typetext": "<string>"
      }
    },
    "type": "object"
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/dns",
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
