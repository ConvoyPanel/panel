# POST /cluster/sdn/lock

Acquire global lock for SDN configuration

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| allow-pending | boolean | no | if true, allow acquiring lock even though there are pending changes |

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
  "description": "Acquire global lock for SDN configuration",
  "method": "POST",
  "name": "lock",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "allow-pending": {
        "default": 0,
        "description": "if true, allow acquiring lock even though there are pending changes",
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
