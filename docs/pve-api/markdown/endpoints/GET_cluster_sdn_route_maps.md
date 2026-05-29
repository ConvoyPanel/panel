# GET /cluster/sdn/route-maps

List Route Maps

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| running | boolean | no | Display running config. |

## Returns

```json
{
  "items": {
    "properties": {
      "id": {
        "description": "The SDN route map identifier",
        "format": "pve-sdn-route-map-id",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "entries/{id}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only returns route maps where you have 'SDN.Audit' or 'SDN.Allocate' permissions.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List Route Maps",
  "method": "GET",
  "name": "list_route_maps",
  "parameters": {
    "properties": {
      "running": {
        "description": "Display running config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "description": "Only returns route maps where you have 'SDN.Audit' or 'SDN.Allocate' permissions.",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "id": {
          "description": "The SDN route map identifier",
          "format": "pve-sdn-route-map-id",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "entries/{id}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
