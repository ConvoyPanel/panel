# DELETE /cluster/config/nodes/{node}

Removes a node from the cluster configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "type": "null"
}
```

## Permissions

Not specified.

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Removes a node from the cluster configuration.",
  "method": "DELETE",
  "name": "delnode",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
