# GET /cluster/resources

Resources index (cluster wide).

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | Resource type. |

## Returns

```json
{
  "items": {
    "properties": {
      "cgroup-mode": {
        "description": "The cgroup mode the node operates under (for type 'node').",
        "optional": 1,
        "type": "integer"
      },
      "content": {
        "description": "Allowed storage content types (for type 'storage').",
        "format": "pve-storage-content-list",
        "optional": 1,
        "type": "string"
      },
      "cpu": {
        "description": "CPU utilization (for types 'node', 'qemu' and 'lxc').",
        "minimum": 0,
        "optional": 1,
        "renderer": "fraction_as_percentage",
        "type": "number"
      },
      "disk": {
        "description": "Used disk space in bytes (for type 'storage'), used root image space for VMs (for types 'qemu' and 'lxc').",
        "minimum": 0,
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "diskread": {
        "description": "The number of bytes the guest read from its block devices since the guest was started. This info is not available for all storage types. (for types 'qemu' and 'lxc')",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "diskwrite": {
        "description": "The number of bytes the guest wrote to its block devices since the guest was started. This info is not available for all storage types. (for types 'qemu' and 'lxc')",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "hastate": {
        "description": "HA service status (for HA managed VMs).",
        "optional": 1,
        "type": "string"
      },
      "host-arch": {
        "default": "x86_64",
        "description": "The node's CPU architecture. (for type 'node').",
        "enum": [
          "x86_64",
          "aarch64"
        ],
        "optional": 1,
        "type": "string"
      },
      "id": {
        "description": "Resource id.",
        "type": "string"
      },
      "level": {
        "description": "Support level (for type 'node').",
        "optional": 1,
        "type": "string"
      },
      "lock": {
        "description": "The guest's current config lock (for types 'qemu' and 'lxc')",
        "optional": 1,
        "type": "string"
      },
      "maxcpu": {
        "description": "Number of available CPUs (for types 'node', 'qemu' and 'lxc').",
        "minimum": 0,
        "optional": 1,
        "type": "number"
      },
      "maxdisk": {
        "description": "Storage size in bytes (for type 'storage'), root image size for VMs (for types 'qemu' and 'lxc').",
        "minimum": 0,
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "maxmem": {
        "description": "Number of available memory in bytes (for types 'node', 'qemu' and 'lxc').",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "mem": {
        "description": "Used memory in bytes (for types 'node', 'qemu' and 'lxc').",
        "minimum": 0,
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "memhost": {
        "description": "Used memory in bytes from the point of view of the host (for types 'qemu').",
        "minimum": 0,
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "name": {
        "description": "Name of the resource.",
        "optional": 1,
        "type": "string"
      },
      "netin": {
        "description": "The amount of traffic in bytes that was sent to the guest over the network since it was started. (for types 'qemu' and 'lxc')",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "netout": {
        "description": "The amount of traffic in bytes that was sent from the guest over the network since it was started. (for types 'qemu' and 'lxc')",
        "optional": 1,
        "renderer": "bytes",
        "type": "integer"
      },
      "network": {
        "description": "The name of a Network entity (for type 'network').",
        "optional": 1,
        "type": "string"
      },
      "network-type": {
        "description": "The type of network resource (for type 'network').",
        "enum": [
          "fabric",
          "zone"
        ],
        "optional": 1,
        "type": "string"
      },
      "node": {
        "description": "The cluster node name (for types 'node', 'storage', 'qemu', and 'lxc').",
        "format": "pve-node",
        "optional": 1,
        "type": "string"
      },
      "plugintype": {
        "description": "More specific type, if available.",
        "optional": 1,
        "type": "string"
      },
      "pool": {
        "description": "The pool name (for types 'pool', 'qemu' and 'lxc').",
        "optional": 1,
        "type": "string"
      },
      "protocol": {
        "description": "The protocol of a fabric (for type 'network', network-type 'fabric').",
        "optional": 1,
        "type": "string"
      },
      "sdn": {
        "description": "The name of an SDN entity (for type 'sdn')",
        "optional": 1,
        "type": "string"
      },
      "shared": {
        "description": "Determines whether the storage is shared",
        "optional": 1,
        "type": "boolean"
      },
      "status": {
        "description": "Resource type dependent status.",
        "optional": 1,
        "type": "string"
      },
      "storage": {
        "description": "The storage identifier (for type 'storage').",
        "format": "pve-storage-id",
        "format_description": "storage ID",
        "optional": 1,
        "type": "string"
      },
      "tags": {
        "description": "The guest's tags (for types 'qemu' and 'lxc')",
        "optional": 1,
        "type": "string"
      },
      "template": {
        "default": 0,
        "description": "Determines if the guest is a template. (for types 'qemu' and 'lxc')",
        "optional": 1,
        "type": "boolean"
      },
      "type": {
        "description": "Resource type.",
        "enum": [
          "node",
          "storage",
          "pool",
          "qemu",
          "lxc",
          "openvz",
          "sdn",
          "network"
        ],
        "type": "string"
      },
      "uptime": {
        "description": "Uptime of node or virtual guest in seconds (for types 'node', 'qemu' and 'lxc').",
        "optional": 1,
        "renderer": "duration",
        "type": "integer"
      },
      "vmid": {
        "description": "The numerical vmid (for types 'qemu' and 'lxc').",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "optional": 1,
        "type": "integer"
      },
      "zone-type": {
        "description": "The type of an SDN zone (for type 'sdn').",
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
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Resources index (cluster wide).",
  "method": "GET",
  "name": "resources",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "type": {
        "description": "Resource type.",
        "enum": [
          "vm",
          "storage",
          "node",
          "sdn"
        ],
        "optional": 1,
        "type": "string"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {
        "cgroup-mode": {
          "description": "The cgroup mode the node operates under (for type 'node').",
          "optional": 1,
          "type": "integer"
        },
        "content": {
          "description": "Allowed storage content types (for type 'storage').",
          "format": "pve-storage-content-list",
          "optional": 1,
          "type": "string"
        },
        "cpu": {
          "description": "CPU utilization (for types 'node', 'qemu' and 'lxc').",
          "minimum": 0,
          "optional": 1,
          "renderer": "fraction_as_percentage",
          "type": "number"
        },
        "disk": {
          "description": "Used disk space in bytes (for type 'storage'), used root image space for VMs (for types 'qemu' and 'lxc').",
          "minimum": 0,
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "diskread": {
          "description": "The number of bytes the guest read from its block devices since the guest was started. This info is not available for all storage types. (for types 'qemu' and 'lxc')",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "diskwrite": {
          "description": "The number of bytes the guest wrote to its block devices since the guest was started. This info is not available for all storage types. (for types 'qemu' and 'lxc')",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "hastate": {
          "description": "HA service status (for HA managed VMs).",
          "optional": 1,
          "type": "string"
        },
        "host-arch": {
          "default": "x86_64",
          "description": "The node's CPU architecture. (for type 'node').",
          "enum": [
            "x86_64",
            "aarch64"
          ],
          "optional": 1,
          "type": "string"
        },
        "id": {
          "description": "Resource id.",
          "type": "string"
        },
        "level": {
          "description": "Support level (for type 'node').",
          "optional": 1,
          "type": "string"
        },
        "lock": {
          "description": "The guest's current config lock (for types 'qemu' and 'lxc')",
          "optional": 1,
          "type": "string"
        },
        "maxcpu": {
          "description": "Number of available CPUs (for types 'node', 'qemu' and 'lxc').",
          "minimum": 0,
          "optional": 1,
          "type": "number"
        },
        "maxdisk": {
          "description": "Storage size in bytes (for type 'storage'), root image size for VMs (for types 'qemu' and 'lxc').",
          "minimum": 0,
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "maxmem": {
          "description": "Number of available memory in bytes (for types 'node', 'qemu' and 'lxc').",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "mem": {
          "description": "Used memory in bytes (for types 'node', 'qemu' and 'lxc').",
          "minimum": 0,
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "memhost": {
          "description": "Used memory in bytes from the point of view of the host (for types 'qemu').",
          "minimum": 0,
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "name": {
          "description": "Name of the resource.",
          "optional": 1,
          "type": "string"
        },
        "netin": {
          "description": "The amount of traffic in bytes that was sent to the guest over the network since it was started. (for types 'qemu' and 'lxc')",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "netout": {
          "description": "The amount of traffic in bytes that was sent from the guest over the network since it was started. (for types 'qemu' and 'lxc')",
          "optional": 1,
          "renderer": "bytes",
          "type": "integer"
        },
        "network": {
          "description": "The name of a Network entity (for type 'network').",
          "optional": 1,
          "type": "string"
        },
        "network-type": {
          "description": "The type of network resource (for type 'network').",
          "enum": [
            "fabric",
            "zone"
          ],
          "optional": 1,
          "type": "string"
        },
        "node": {
          "description": "The cluster node name (for types 'node', 'storage', 'qemu', and 'lxc').",
          "format": "pve-node",
          "optional": 1,
          "type": "string"
        },
        "plugintype": {
          "description": "More specific type, if available.",
          "optional": 1,
          "type": "string"
        },
        "pool": {
          "description": "The pool name (for types 'pool', 'qemu' and 'lxc').",
          "optional": 1,
          "type": "string"
        },
        "protocol": {
          "description": "The protocol of a fabric (for type 'network', network-type 'fabric').",
          "optional": 1,
          "type": "string"
        },
        "sdn": {
          "description": "The name of an SDN entity (for type 'sdn')",
          "optional": 1,
          "type": "string"
        },
        "shared": {
          "description": "Determines whether the storage is shared",
          "optional": 1,
          "type": "boolean"
        },
        "status": {
          "description": "Resource type dependent status.",
          "optional": 1,
          "type": "string"
        },
        "storage": {
          "description": "The storage identifier (for type 'storage').",
          "format": "pve-storage-id",
          "format_description": "storage ID",
          "optional": 1,
          "type": "string"
        },
        "tags": {
          "description": "The guest's tags (for types 'qemu' and 'lxc')",
          "optional": 1,
          "type": "string"
        },
        "template": {
          "default": 0,
          "description": "Determines if the guest is a template. (for types 'qemu' and 'lxc')",
          "optional": 1,
          "type": "boolean"
        },
        "type": {
          "description": "Resource type.",
          "enum": [
            "node",
            "storage",
            "pool",
            "qemu",
            "lxc",
            "openvz",
            "sdn",
            "network"
          ],
          "type": "string"
        },
        "uptime": {
          "description": "Uptime of node or virtual guest in seconds (for types 'node', 'qemu' and 'lxc').",
          "optional": 1,
          "renderer": "duration",
          "type": "integer"
        },
        "vmid": {
          "description": "The numerical vmid (for types 'qemu' and 'lxc').",
          "format": "pve-vmid",
          "maximum": 999999999,
          "minimum": 100,
          "optional": 1,
          "type": "integer"
        },
        "zone-type": {
          "description": "The type of an SDN zone (for type 'sdn').",
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
