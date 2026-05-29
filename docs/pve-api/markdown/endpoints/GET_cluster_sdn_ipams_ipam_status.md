# GET /cluster/sdn/ipams/{ipam}/status

List PVE IPAM Entries

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| ipam | string | yes | The SDN ipam object identifier. |

## Request parameters

None.

## Returns

```json
{
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/zones/<zone>/<vnet>'",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List PVE IPAM Entries",
  "method": "GET",
  "name": "ipamindex",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "ipam": {
        "description": "The SDN ipam object identifier.",
        "minLength": 2,
        "pattern": "[a-zA-Z][a-zA-Z0-9]*[a-zA-Z0-9]",
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Only list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions on '/sdn/zones/<zone>/<vnet>'",
    "user": "all"
  },
  "protected": 1,
  "returns": {
    "type": "array"
  }
}
```
