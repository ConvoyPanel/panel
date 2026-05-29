# GET /cluster/mapping/usb/{id}

Get USB Mapping.

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
      "/mapping/usb/{id}",
      [
        "Mapping.Audit"
      ]
    ],
    [
      "perm",
      "/mapping/usb/{id}",
      [
        "Mapping.Use"
      ]
    ],
    [
      "perm",
      "/mapping/usb/{id}",
      [
        "Mapping.Modify"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get USB Mapping.",
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
        "/mapping/usb/{id}",
        [
          "Mapping.Audit"
        ]
      ],
      [
        "perm",
        "/mapping/usb/{id}",
        [
          "Mapping.Use"
        ]
      ],
      [
        "perm",
        "/mapping/usb/{id}",
        [
          "Mapping.Modify"
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
