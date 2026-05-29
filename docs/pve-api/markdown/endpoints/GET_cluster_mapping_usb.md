# GET /cluster/mapping/usb

List USB Hardware Mappings

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| check-node | string | no | If given, checks the configurations on the given node for correctness, and adds relevant errors to the devices. |

## Returns

```json
{
  "items": {
    "properties": {
      "description": {
        "description": "A description of the logical mapping.",
        "type": "string"
      },
      "error": {
        "description": "A list of errors when 'check_node' is given.",
        "items": {
          "properties": {
            "message": {
              "description": "The message of the error",
              "type": "string"
            },
            "severity": {
              "description": "The severity of the error",
              "type": "string"
            }
          },
          "type": "object"
        }
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
  "description": "Only lists entries where you have 'Mapping.Modify', 'Mapping.Use' or 'Mapping.Audit' permissions on '/mapping/usb/<id>'.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List USB Hardware Mappings",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "check-node": {
        "description": "If given, checks the configurations on the given node for correctness, and adds relevant errors to the devices.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "Only lists entries where you have 'Mapping.Modify', 'Mapping.Use' or 'Mapping.Audit' permissions on '/mapping/usb/<id>'.",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "description": {
          "description": "A description of the logical mapping.",
          "type": "string"
        },
        "error": {
          "description": "A list of errors when 'check_node' is given.",
          "items": {
            "properties": {
              "message": {
                "description": "The message of the error",
                "type": "string"
              },
              "severity": {
                "description": "The severity of the error",
                "type": "string"
              }
            },
            "type": "object"
          }
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
