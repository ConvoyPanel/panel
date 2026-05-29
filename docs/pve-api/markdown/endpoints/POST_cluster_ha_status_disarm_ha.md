# POST /cluster/ha/status/disarm-ha

Request disarming the HA stack, releasing all watchdogs cluster-wide.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| resource-mode | string | yes | Controls how HA managed resources are handled while disarmed. The current state of resources is not affected. 'freeze': new commands and state changes are not applied. 'ignore': resources are removed from HA tracking and can be managed as if they were not HA managed. |

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
      "Sys.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Request disarming the HA stack, releasing all watchdogs cluster-wide.",
  "method": "POST",
  "name": "disarm-ha",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "resource-mode": {
        "description": "Controls how HA managed resources are handled while disarmed. The current state of resources is not affected. 'freeze': new commands and state changes are not applied. 'ignore': resources are removed from HA tracking and can be managed as if they were not HA managed.",
        "enum": [
          "freeze",
          "ignore"
        ],
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Console"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
