# GET /nodes/{node}/qemu/{vmid}/migrate

Get preconditions for migration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| target | string | no | Target node. |

## Returns

```json
{
  "properties": {
    "allowed_nodes": {
      "description": "List of nodes allowed for migration.",
      "items": {
        "description": "An allowed node",
        "type": "string"
      },
      "optional": 1,
      "type": "array"
    },
    "dependent-ha-resources": {
      "description": "HA resources, which will be migrated to the same target node as the VM, because these are in positive affinity with the VM.",
      "items": {
        "description": "The '<ty>:<id>' resource IDs of a HA resource with a positive affinity rule to this VM.",
        "type": "string"
      },
      "optional": 1,
      "type": "array"
    },
    "has-dbus-vmstate": {
      "description": "Whether the VM host supports migrating additional VM state, such as conntrack entries.",
      "type": "boolean"
    },
    "local_disks": {
      "description": "List local disks including CD-Rom, unused and not referenced disks",
      "items": {
        "properties": {
          "cdrom": {
            "description": "True if the disk is a cdrom.",
            "type": "boolean"
          },
          "is_unused": {
            "description": "True if the disk is unused.",
            "type": "boolean"
          },
          "size": {
            "description": "The size of the disk in bytes.",
            "type": "integer"
          },
          "volid": {
            "description": "The volid of the disk.",
            "type": "string"
          }
        },
        "type": "object"
      },
      "type": "array"
    },
    "local_resources": {
      "description": "List local resources (e.g. pci, usb) that block migration.",
      "items": {
        "description": "A local resource",
        "type": "string"
      },
      "type": "array"
    },
    "mapped-resource-info": {
      "description": "Object of mapped resources with additional information such if they're live migratable.",
      "type": "object"
    },
    "mapped-resources": {
      "description": "List of mapped resources e.g. pci, usb. Deprecated, use 'mapped-resource-info' instead.",
      "items": {
        "description": "A mapped resource",
        "type": "string"
      },
      "type": "array"
    },
    "not_allowed_nodes": {
      "description": "List of not allowed nodes with additional information.",
      "optional": 1,
      "properties": {
        "blocking-ha-resources": {
          "description": "HA resources, which are blocking the VM from being migrated to the node.",
          "items": {
            "description": "A blocking HA resource",
            "properties": {
              "cause": {
                "description": "The reason why the HA resource is blocking the migration.",
                "enum": [
                  "node-affinity",
                  "resource-affinity"
                ],
                "type": "string"
              },
              "sid": {
                "description": "The blocking HA resource id",
                "type": "string"
              }
            },
            "type": "object"
          },
          "optional": 1,
          "type": "array"
        },
        "unavailable_storages": {
          "description": "A list of not available storages.",
          "items": {
            "description": "A storage",
            "type": "string"
          },
          "optional": 1,
          "type": "array"
        }
      },
      "type": "object"
    },
    "running": {
      "description": "Determines if the VM is running.",
      "type": "boolean"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "VM.Migrate"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Get preconditions for migration.",
  "method": "GET",
  "name": "migrate_vm_precondition",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "target": {
        "description": "Target node.",
        "format": "pve-node",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "VM.Migrate"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "properties": {
      "allowed_nodes": {
        "description": "List of nodes allowed for migration.",
        "items": {
          "description": "An allowed node",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "dependent-ha-resources": {
        "description": "HA resources, which will be migrated to the same target node as the VM, because these are in positive affinity with the VM.",
        "items": {
          "description": "The '<ty>:<id>' resource IDs of a HA resource with a positive affinity rule to this VM.",
          "type": "string"
        },
        "optional": 1,
        "type": "array"
      },
      "has-dbus-vmstate": {
        "description": "Whether the VM host supports migrating additional VM state, such as conntrack entries.",
        "type": "boolean"
      },
      "local_disks": {
        "description": "List local disks including CD-Rom, unused and not referenced disks",
        "items": {
          "properties": {
            "cdrom": {
              "description": "True if the disk is a cdrom.",
              "type": "boolean"
            },
            "is_unused": {
              "description": "True if the disk is unused.",
              "type": "boolean"
            },
            "size": {
              "description": "The size of the disk in bytes.",
              "type": "integer"
            },
            "volid": {
              "description": "The volid of the disk.",
              "type": "string"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "local_resources": {
        "description": "List local resources (e.g. pci, usb) that block migration.",
        "items": {
          "description": "A local resource",
          "type": "string"
        },
        "type": "array"
      },
      "mapped-resource-info": {
        "description": "Object of mapped resources with additional information such if they're live migratable.",
        "type": "object"
      },
      "mapped-resources": {
        "description": "List of mapped resources e.g. pci, usb. Deprecated, use 'mapped-resource-info' instead.",
        "items": {
          "description": "A mapped resource",
          "type": "string"
        },
        "type": "array"
      },
      "not_allowed_nodes": {
        "description": "List of not allowed nodes with additional information.",
        "optional": 1,
        "properties": {
          "blocking-ha-resources": {
            "description": "HA resources, which are blocking the VM from being migrated to the node.",
            "items": {
              "description": "A blocking HA resource",
              "properties": {
                "cause": {
                  "description": "The reason why the HA resource is blocking the migration.",
                  "enum": [
                    "node-affinity",
                    "resource-affinity"
                  ],
                  "type": "string"
                },
                "sid": {
                  "description": "The blocking HA resource id",
                  "type": "string"
                }
              },
              "type": "object"
            },
            "optional": 1,
            "type": "array"
          },
          "unavailable_storages": {
            "description": "A list of not available storages.",
            "items": {
              "description": "A storage",
              "type": "string"
            },
            "optional": 1,
            "type": "array"
          }
        },
        "type": "object"
      },
      "running": {
        "description": "Determines if the VM is running.",
        "type": "boolean"
      }
    },
    "type": "object"
  }
}
```
