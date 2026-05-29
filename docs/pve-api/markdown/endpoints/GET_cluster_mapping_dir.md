# GET /cluster/mapping/dir

List directory mapping

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| check-node | string | no | If given, checks the configurations on the given node for correctness, and adds relevant diagnostics for the directory to the response. |

## Returns

```json
{
  "items": {
    "properties": {
      "checks": {
        "description": "A list of checks, only present if 'check-node' is set.",
        "items": {
          "properties": {
            "message": {
              "description": "The message of the error",
              "type": "string"
            },
            "severity": {
              "description": "The severity of the error",
              "enum": [
                "warning",
                "error"
              ],
              "type": "string"
            }
          },
          "type": "object"
        },
        "optional": 1,
        "type": "array"
      },
      "description": {
        "description": "A description of the logical mapping.",
        "type": "string"
      },
      "id": {
        "description": "The logical ID of the mapping.",
        "type": "string"
      },
      "map": {
        "description": "The entries of the mapping.",
        "items": {
          "description": "A mapping for a node.",
          "type": "string"
        },
        "type": "array"
      }
    },
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
  "description": "Only lists entries where you have 'Mapping.Modify', 'Mapping.Use' or 'Mapping.Audit' permissions on '/mapping/dir/<id>'.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List directory mapping",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "check-node": {
        "description": "If given, checks the configurations on the given node for correctness, and adds relevant diagnostics for the directory to the response.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "Only lists entries where you have 'Mapping.Modify', 'Mapping.Use' or 'Mapping.Audit' permissions on '/mapping/dir/<id>'.",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "checks": {
          "description": "A list of checks, only present if 'check-node' is set.",
          "items": {
            "properties": {
              "message": {
                "description": "The message of the error",
                "type": "string"
              },
              "severity": {
                "description": "The severity of the error",
                "enum": [
                  "warning",
                  "error"
                ],
                "type": "string"
              }
            },
            "type": "object"
          },
          "optional": 1,
          "type": "array"
        },
        "description": {
          "description": "A description of the logical mapping.",
          "type": "string"
        },
        "id": {
          "description": "The logical ID of the mapping.",
          "type": "string"
        },
        "map": {
          "description": "The entries of the mapping.",
          "items": {
            "description": "A mapping for a node.",
            "type": "string"
          },
          "type": "array"
        }
      },
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
