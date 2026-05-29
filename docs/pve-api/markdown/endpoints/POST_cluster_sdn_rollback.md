# POST /cluster/sdn/rollback

Rollback pending changes to SDN configuration

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| lock-token | string | no | the token for unlocking the global SDN configuration |
| release-lock | boolean | no | When lock-token has been provided and configuration successfully rollbacked, release the lock automatically afterwards |

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
    "/sdn",
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
  "description": "Rollback pending changes to SDN configuration",
  "method": "POST",
  "name": "rollback",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "release-lock": {
        "default": 1,
        "description": "When lock-token has been provided and configuration successfully rollbacked, release the lock automatically afterwards",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn",
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
