# DELETE /cluster/firewall/ipset/{name}/{cidr}

Remove IP or Network from IPSet.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cidr | string | yes | Network/IP specification in CIDR format. |
| name | string | yes | IP set name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| digest | string | no | Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications. |

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
  "description": "Remove IP or Network from IPSet.",
  "method": "DELETE",
  "name": "remove_ip",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cidr": {
        "description": "Network/IP specification in CIDR format.",
        "format": "IPorCIDRorAlias",
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
