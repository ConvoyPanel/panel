# POST /cluster/sdn/ipams

Create a new sdn ipam object.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| ipam | string | yes | The SDN ipam object identifier. |
| type | string | yes | Plugin type. |
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
  "description": "Create a new sdn ipam object.",
  "method": "POST",
  "name": "create",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
      "type": {
        "description": "Plugin type.",
        "enum": [
          "netbox",
          "phpipam",
          "pve"
        ],
        "format": "pve-configid",
        "type": "string"
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
