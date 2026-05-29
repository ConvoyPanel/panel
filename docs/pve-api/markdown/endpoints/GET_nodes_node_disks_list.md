# GET /nodes/{node}/disks/list

List local disks.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| include-partitions | boolean | no | Also include partitions. |
| skipsmart | boolean | no | Skip smart checks. |
| type | string | no | Only list specific types of disks. |

## Returns

```json
{
  "items": {
    "properties": {
      "devpath": {
        "description": "The device path",
        "type": "string"
      },
      "gpt": {
        "type": "boolean"
      },
      "health": {
        "optional": 1,
        "type": "string"
      },
      "model": {
        "optional": 1,
        "type": "string"
      },
      "mounted": {
        "type": "boolean"
      },
      "osdid": {
        "type": "integer"
      },
      "osdid-list": {
        "items": {
          "type": "integer"
        },
        "type": "array"
      },
      "parent": {
        "description": "For partitions only. The device path of the disk the partition resides on.",
        "optional": 1,
        "type": "string"
      },
      "serial": {
        "optional": 1,
        "type": "string"
      },
      "size": {
        "type": "integer"
      },
      "used": {
        "optional": 1,
        "type": "string"
      },
      "vendor": {
        "optional": 1,
        "type": "string"
      },
      "wwn": {
        "optional": 1,
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
    "or",
    [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ],
    [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Audit"
      ]
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List local disks.",
  "method": "GET",
  "name": "list",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "include-partitions": {
        "default": 0,
        "description": "Also include partitions.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "skipsmart": {
        "default": 0,
        "description": "Skip smart checks.",
        "optional": 1,
        "type": "boolean",
        "typetext": "<boolean>"
      },
      "type": {
        "description": "Only list specific types of disks.",
        "enum": [
          "unused",
          "journal_disks"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "check": [
      "or",
      [
        "perm",
        "/",
        [
          "Sys.Audit"
        ]
      ],
      [
        "perm",
        "/nodes/{node}",
        [
          "Sys.Audit"
        ]
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {
        "devpath": {
          "description": "The device path",
          "type": "string"
        },
        "gpt": {
          "type": "boolean"
        },
        "health": {
          "optional": 1,
          "type": "string"
        },
        "model": {
          "optional": 1,
          "type": "string"
        },
        "mounted": {
          "type": "boolean"
        },
        "osdid": {
          "type": "integer"
        },
        "osdid-list": {
          "items": {
            "type": "integer"
          },
          "type": "array"
        },
        "parent": {
          "description": "For partitions only. The device path of the disk the partition resides on.",
          "optional": 1,
          "type": "string"
        },
        "serial": {
          "optional": 1,
          "type": "string"
        },
        "size": {
          "type": "integer"
        },
        "used": {
          "optional": 1,
          "type": "string"
        },
        "vendor": {
          "optional": 1,
          "type": "string"
        },
        "wwn": {
          "optional": 1,
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
