# GET /cluster/sdn/prefix-lists

List Prefix Lists

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| pending | boolean | no | Display pending config. |
| running | boolean | no | Display running config. |
| verbose | boolean | no | If 0, only returns id - otherwise returns all properties. |

## Returns

```json
{
  "items": {
    "properties": {},
    "type": "object"
  },
  "links": [
    {
      "href": "{id}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Only returns prefix list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List Prefix Lists",
  "method": "GET",
  "name": "list_prefix_lists",
  "parameters": {
    "properties": {
      "pending": {
        "description": "Display pending config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "running": {
        "description": "Display running config.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "verbose": {
        "description": "If 0, only returns id - otherwise returns all properties.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      }
    }
  },
  "permissions": {
    "description": "Only returns prefix list entries where you have 'SDN.Audit' or 'SDN.Allocate' permissions.",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {},
      "type": "object"
    },
    "links": [
      {
        "href": "{id}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
