# GET /nodes/{node}/capabilities/qemu/cpu

List all custom and default CPU models.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| arch | string | no | Virtual processor architecture. Defaults to the host architecture. |

## Returns

```json
{
  "items": {
    "properties": {
      "abstract": {
        "description": "True for PVE-internal abstract profiles like x86-64-v2, -v3, -v4. These do not correspond to a QEMU CPU type and cannot be used as a custom model's 'reported-model'.",
        "optional": 1,
        "type": "boolean"
      },
      "custom": {
        "description": "True if this is a custom CPU model.",
        "type": "boolean"
      },
      "name": {
        "description": "Name of the CPU model. Identifies it for subsequent API calls. Prefixed with 'custom-' for custom models.",
        "type": "string"
      },
      "vendor": {
        "description": "CPU vendor visible to the guest when this model is selected. Vendor of 'reported-model' in case of custom models.",
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{name}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "description": "Custom models are filtered to those the current user has any of Mapping.{Audit,Use,Modify} on /mapping/cpu/<name>; Sys.Audit on /nodes continues to grant visibility of all custom models for back-compat.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List all custom and default CPU models.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "arch": {
        "description": "Virtual processor architecture. Defaults to the host architecture.",
        "enum": [
          "x86_64",
          "aarch64"
        ],
        "optional": 1,
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "Custom models are filtered to those the current user has any of Mapping.{Audit,Use,Modify} on /mapping/cpu/<name>; Sys.Audit on /nodes continues to grant visibility of all custom models for back-compat.",
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "abstract": {
          "description": "True for PVE-internal abstract profiles like x86-64-v2, -v3, -v4. These do not correspond to a QEMU CPU type and cannot be used as a custom model's 'reported-model'.",
          "optional": 1,
          "type": "boolean"
        },
        "custom": {
          "description": "True if this is a custom CPU model.",
          "type": "boolean"
        },
        "name": {
          "description": "Name of the CPU model. Identifies it for subsequent API calls. Prefixed with 'custom-' for custom models.",
          "type": "string"
        },
        "vendor": {
          "description": "CPU vendor visible to the guest when this model is selected. Vendor of 'reported-model' in case of custom models.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{name}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
