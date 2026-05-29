# POST /cluster/notifications/targets/{name}/test

Send a test notification to a provided target.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | Name of the target. |

## Request parameters

None.

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
    "or",
    [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Modify"
      ]
    ],
    [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Audit"
      ]
    ],
    [
      "perm",
      "/mapping/notifications",
      [
        "Mapping.Use"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Send a test notification to a provided target.",
  "method": "POST",
  "name": "test_target",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "Name of the target.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "or",
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Modify"
        ]
      ],
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Audit"
        ]
      ],
      [
        "perm",
        "/mapping/notifications",
        [
          "Mapping.Use"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
