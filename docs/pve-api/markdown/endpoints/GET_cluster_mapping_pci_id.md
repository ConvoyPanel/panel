# GET /cluster/mapping/pci/{id}

Get PCI Mapping.

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
      "/mapping/pci/{id}",
      [
        "Mapping.Use"
      ]
    ],
    [
      "perm",
      "/mapping/pci/{id}",
      [
        "Mapping.Modify"
      ]
    ],
    [
      "perm",
      "/mapping/pci/{id}",
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
  "description": "Get PCI Mapping.",
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
        "/mapping/pci/{id}",
        [
          "Mapping.Use"
        ]
      ],
      [
        "perm",
        "/mapping/pci/{id}",
        [
          "Mapping.Modify"
        ]
      ],
      [
        "perm",
        "/mapping/pci/{id}",
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
