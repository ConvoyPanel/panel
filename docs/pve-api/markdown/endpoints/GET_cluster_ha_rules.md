# GET /cluster/ha/rules

Get HA rules.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| resource | string | no | Limit the returned list to rules affecting the specified resource. |
| type | string | no | Limit the returned list to the specified rule type. |

## Returns

```json
{
  "items": {
    "links": [
      {
        "href": "{rule}",
        "rel": "child"
      }
    ],
    "properties": {
      "rule": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
    [
      "Sys.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get HA rules.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "resource": {
        "description": "Limit the returned list to rules affecting the specified resource.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "description": "Limit the returned list to the specified rule type.",
        "enum": [
          "node-affinity",
          "resource-affinity"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "items": {
      "links": [
        {
          "href": "{rule}",
          "rel": "child"
        }
      ],
      "properties": {
        "rule": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
