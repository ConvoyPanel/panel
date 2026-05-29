# GET /cluster/mapping/dir/{id}

Get directory mapping.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes |  |

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
    "or",
    [
      "perm",
      "/mapping/dir/{id}",
      [
        "Mapping.Use"
      ]
    ],
    [
      "perm",
      "/mapping/dir/{id}",
      [
        "Mapping.Modify"
      ]
    ],
    [
      "perm",
      "/mapping/dir/{id}",
      [
        "Mapping.Audit"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get directory mapping.",
  "method": "GET",
  "name": "get",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
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
        "/mapping/dir/{id}",
        [
          "Mapping.Use"
        ]
      ],
      [
        "perm",
        "/mapping/dir/{id}",
        [
          "Mapping.Modify"
        ]
      ],
      [
        "perm",
        "/mapping/dir/{id}",
        [
          "Mapping.Audit"
        ]
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "object"
  }
}
```
