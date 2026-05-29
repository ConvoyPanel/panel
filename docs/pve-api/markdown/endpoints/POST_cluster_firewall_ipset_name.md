# POST /cluster/firewall/ipset/{name}

Add IP or Network to IPSet.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | IP set name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cidr | string | yes | Network/IP specification in CIDR format. |
| comment | string | no |  |
| nomatch | boolean | no |  |

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
    "/",
    [
      "Sys.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Add IP or Network to IPSet.",
  "method": "POST",
  "name": "create_ip",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cidr": {
        "description": "Network/IP specification in CIDR format.",
        "format": "IPorCIDRorAlias",
        "type": "string",
        "typetext": "<string>"
      },
      "comment": {
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "name": {
        "description": "IP set name.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
      },
      "nomatch": {
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
