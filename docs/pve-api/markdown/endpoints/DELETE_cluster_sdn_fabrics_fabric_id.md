# DELETE /cluster/sdn/fabrics/fabric/{id}

Add a fabric

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Identifier for SDN fabrics |

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
    "perm",
    "/sdn/fabrics/{id}",
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
  "description": "Add a fabric",
  "method": "DELETE",
  "name": "delete_fabric",
  "parameters": {
    "properties": {
      "id": {
        "description": "Identifier for SDN fabrics",
        "format": "pve-sdn-fabric-id",
        "maxLength": 8,
        "minLength": 2,
        "pattern": "[a-zA-Z0-9][a-zA-Z0-9-]{0,6}[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/sdn/fabrics/{id}",
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
