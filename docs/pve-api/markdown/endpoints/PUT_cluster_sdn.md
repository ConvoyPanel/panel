# PUT /cluster/sdn

Apply sdn controller changes && reload.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| lock-token | string | no | the token for unlocking the global SDN configuration |
| release-lock | boolean | no | When lock-token has been provided and configuration successfully committed, release the lock automatically afterwards |

## Returns

```json
{
  "type": "string"
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
  "description": "Apply sdn controller changes && reload.",
  "method": "PUT",
  "name": "reload",
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
        "description": "When lock-token has been provided and configuration successfully committed, release the lock automatically afterwards",
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
    "type": "string"
  }
}
```
