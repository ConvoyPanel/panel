# GET /cluster/qemu/cpu-flags

List of available CPU flags. Currently only implemented for x86_64, returns an empty list for aarch64.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| accel | string | no | Acceleration type to check node compatibility for. |
| arch | string | no | Virtual processor architecture. Defaults to the host architecture. |

## Returns

```json
{
  "items": {
    "properties": {
      "description": {
        "description": "Description of the CPU flag.",
        "optional": 1,
        "type": "string"
      },
      "name": {
        "description": "Name of the CPU flag.",
        "type": "string"
      },
      "supported-on": {
        "description": "List of nodes supporting the flag with the selected acceleration type (\"accel\").",
        "items": {
          "description": "The cluster node name.",
          "format": "pve-node",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
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
      "/nodes",
      [
        "Sys.Audit"
      ]
    ],
    [
      "perm",
      "/mapping/cpu",
      [
        "Mapping.Audit",
        "Mapping.Use",
        "Mapping.Modify"
      ],
      "any",
      1
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List of available CPU flags. Currently only implemented for x86_64, returns an empty list for aarch64.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "accel": {
        "default": "kvm",
        "description": "Acceleration type to check node compatibility for.",
        "enum": [
          "kvm",
          "tcg"
        ],
        "optional": 1,
        "type": "string"
      },
      "arch": {
        "description": "Virtual processor architecture. Defaults to the host architecture.",
        "enum": [
          "x86_64",
          "aarch64"
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
        "/nodes",
        [
          "Sys.Audit"
        ]
      ],
      [
        "perm",
        "/mapping/cpu",
        [
          "Mapping.Audit",
          "Mapping.Use",
          "Mapping.Modify"
        ],
        "any",
        1
      ]
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "description": {
          "description": "Description of the CPU flag.",
          "optional": 1,
          "type": "string"
        },
        "name": {
          "description": "Name of the CPU flag.",
          "type": "string"
        },
        "supported-on": {
          "description": "List of nodes supporting the flag with the selected acceleration type (\"accel\").",
          "items": {
            "description": "The cluster node name.",
            "format": "pve-node",
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
