# GET /cluster/firewall/ipset/{name}/{cidr}

Read IP or Network settings from IPSet.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cidr | string | yes | Network/IP specification in CIDR format. |
| name | string | yes | IP set name. |

## Request parameters

None.

## Returns

```json
{
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Read IP or Network settings from IPSet.",
  "method": "GET",
  "name": "read_ip",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cidr": {
        "description": "Network/IP specification in CIDR format.",
        "format": "IPorCIDRorAlias",
        "type": "string",
        "typetext": "<string>"
      },
      "name": {
        "description": "IP set name.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "object"
  }
}
```
