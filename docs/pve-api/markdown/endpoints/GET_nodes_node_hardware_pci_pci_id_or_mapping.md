# GET /nodes/{node}/hardware/pci/{pci-id-or-mapping}

Index of available pci methods

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| pci-id-or-mapping | string | yes |  |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "method": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{method}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Index of available pci methods",
  "method": "GET",
  "name": "pci_index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "pci-id-or-mapping": {
        "pattern": "(?:(?:[0-9a-fA-F]{4}:)?[0-9a-fA-F]{2}:[0-9a-fA-F]{2}\\.[0-9a-fA-F])|([a-zA-Z][a-zA-Z0-9_-]+)",
        "type": "string"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "method": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{method}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
