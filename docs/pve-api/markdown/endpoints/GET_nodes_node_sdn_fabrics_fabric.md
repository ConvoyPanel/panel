# GET /nodes/{node}/sdn/fabrics/{fabric}

Directory index for SDN fabric status.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| fabric | string | yes | Identifier for SDN fabrics |
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "subdir": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{subdir}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/sdn/fabrics/{fabric}",
    [
      "SDN.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Directory index for SDN fabric status.",
  "method": "GET",
  "name": "diridx",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "fabric": {
        "description": "Identifier for SDN fabrics",
        "format": "pve-sdn-fabric-id",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z0-9][a-zA-Z0-9-]{0,6}[a-zA-Z0-9]",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/fabrics/{fabric}",
      [
        "SDN.Audit"
      ]
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "subdir": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{subdir}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
