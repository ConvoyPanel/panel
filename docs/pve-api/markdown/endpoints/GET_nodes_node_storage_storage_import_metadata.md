# GET /nodes/{node}/storage/{storage}/import-metadata

Get the base parameters for creating a guest which imports data from a foreign importable guest, like an ESXi VM

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| storage | string | yes | The storage identifier. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| volume | string | yes | Volume identifier for the guest archive/entry. |

## Returns

```json
{
  "additionalProperties": 0,
  "description": "Information about how to import a guest.",
  "properties": {
    "create-args": {
      "additionalProperties": 1,
      "description": "Parameters which can be used in a call to create a VM or container.",
      "type": "object"
    },
    "disks": {
      "additionalProperties": 1,
      "description": "Recognised disk volumes as `$bus$id` => `$storeid:$path` map.",
      "optional": 1,
      "type": "object"
    },
    "net": {
      "additionalProperties": 1,
      "description": "Recognised network interfaces as `net$id` => { ...params } object.",
      "optional": 1,
      "type": "object"
    },
    "source": {
      "description": "The type of the import-source of this guest volume.",
      "enum": [
        "esxi"
      ],
      "type": "string"
    },
    "type": {
      "description": "The type of guest this is going to produce.",
      "enum": [
        "vm"
      ],
      "type": "string"
    },
    "warnings": {
      "description": "List of known issues that can affect the import of a guest. Note that lack of warning does not imply that there cannot be any problems.",
      "items": {
        "additionalProperties": 1,
        "properties": {
          "key": {
            "description": "Related subject (config) key of warning.",
            "optional": 1,
            "type": "string"
          },
          "type": {
            "description": "What this warning is about.",
            "enum": [
              "cdrom-image-ignored",
              "efi-state-lost",
              "guest-is-running",
              "nvme-unsupported",
              "ova-needs-extracting",
              "ovmf-with-lsi-unsupported",
              "serial-port-socket-only"
            ],
            "type": "string"
          },
          "value": {
            "description": "Related subject (config) value of warning.",
            "optional": 1,
            "type": "string"
          }
        },
        "type": "object"
      },
      "optional": 1,
      "type": "array"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "description": "You need read access for the volume.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get the base parameters for creating a guest which imports data from a foreign importable guest, like an ESXi VM",
  "method": "GET",
  "name": "get_import_metadata",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "storage": {
        "description": "The storage identifier.",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "type": "string",
        "typetext": "<storage ID>"
      },
      "volume": {
        "description": "Volume identifier for the guest archive/entry.",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "You need read access for the volume.",
    "user": "all"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "additionalProperties": 0,
    "description": "Information about how to import a guest.",
    "properties": {
      "create-args": {
        "additionalProperties": 1,
        "description": "Parameters which can be used in a call to create a VM or container.",
        "type": "object"
      },
      "disks": {
        "additionalProperties": 1,
        "description": "Recognised disk volumes as `$bus$id` => `$storeid:$path` map.",
        "optional": 1,
        "type": "object"
      },
      "net": {
        "additionalProperties": 1,
        "description": "Recognised network interfaces as `net$id` => { ...params } object.",
        "optional": 1,
        "type": "object"
      },
      "source": {
        "description": "The type of the import-source of this guest volume.",
        "enum": [
          "esxi"
        ],
        "type": "string"
      },
      "type": {
        "description": "The type of guest this is going to produce.",
        "enum": [
          "vm"
        ],
        "type": "string"
      },
      "warnings": {
        "description": "List of known issues that can affect the import of a guest. Note that lack of warning does not imply that there cannot be any problems.",
        "items": {
          "additionalProperties": 1,
          "properties": {
            "key": {
              "description": "Related subject (config) key of warning.",
              "optional": 1,
              "type": "string"
            },
            "type": {
              "description": "What this warning is about.",
              "enum": [
                "cdrom-image-ignored",
                "efi-state-lost",
                "guest-is-running",
                "nvme-unsupported",
                "ova-needs-extracting",
                "ovmf-with-lsi-unsupported",
                "serial-port-socket-only"
              ],
              "type": "string"
            },
            "value": {
              "description": "Related subject (config) value of warning.",
              "optional": 1,
              "type": "string"
            }
          },
          "type": "object"
        },
        "optional": 1,
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
