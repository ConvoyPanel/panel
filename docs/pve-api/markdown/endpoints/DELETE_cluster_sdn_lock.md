# DELETE /cluster/sdn/lock

Release global lock for SDN configuration

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| force | boolean | no | if true, allow releasing lock without providing the token |
| lock-token | string | no | the token for unlocking the global SDN configuration |

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
  "description": "Release global lock for SDN configuration",
  "method": "DELETE",
  "name": "release_lock",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "force": {
        "default": 0,
        "description": "if true, allow releasing lock without providing the token",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "lock-token": {
        "description": "the token for unlocking the global SDN configuration",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
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
