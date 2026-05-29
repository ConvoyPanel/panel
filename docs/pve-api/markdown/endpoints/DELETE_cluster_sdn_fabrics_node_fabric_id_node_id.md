# DELETE /cluster/sdn/fabrics/node/{fabric_id}/{node_id}

Add a node

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| fabric_id | string | yes | Identifier for SDN fabrics |
| node_id | string | yes | Identifier for nodes in an SDN fabric |

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
    "and",
    [
      "perm",
      "/sdn/fabrics/{fabric_id}",
      [
        "SDN.Allocate"
      ]
    ],
    [
      "perm",
      "/nodes/{node_id}",
      [
        "Sys.Modify"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Add a node",
  "method": "DELETE",
  "name": "delete_node",
  "parameters": {
    "properties": {
      "fabric_id": {
        "description": "Identifier for SDN fabrics",
        "format": "pve-sdn-fabric-id",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z0-9][a-zA-Z0-9-]{0,6}[a-zA-Z0-9]",
        "type": "string"
      },
      "node_id": {
        "description": "Identifier for nodes in an SDN fabric",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "and",
      [
        "perm",
        "/sdn/fabrics/{fabric_id}",
        [
          "SDN.Allocate"
        ]
      ],
      [
        "perm",
        "/nodes/{node_id}",
        [
          "Sys.Modify"
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
