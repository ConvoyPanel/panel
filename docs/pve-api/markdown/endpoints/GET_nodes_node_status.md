# GET /nodes/{node}/status

Read node status

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "additionalProperties": 1,
  "properties": {
    "boot-info": {
      "description": "Meta-information about the boot mode.",
      "properties": {
        "mode": {
          "description": "Through which firmware the system got booted.",
          "enum": [
            "efi",
            "legacy-bios"
          ],
          "type": "string"
        },
        "secureboot": {
          "description": "System is booted in secure mode, only applicable for the \"efi\" mode.",
          "optional": 1,
          "type": "boolean"
        }
      },
      "type": "object"
    },
    "cpu": {
      "description": "The current cpu usage.",
      "type": "number"
    },
    "cpuinfo": {
      "properties": {
        "cores": {
          "description": "The number of physical cores of the CPU.",
          "type": "integer"
        },
        "cpus": {
          "description": "The number of logical threads of the CPU.",
          "type": "integer"
        },
        "model": {
          "description": "The CPU model",
          "type": "string"
        },
        "sockets": {
          "description": "The number of logical threads of the CPU.",
          "type": "integer"
        }
      },
      "type": "object"
    },
    "current-kernel": {
      "description": "Meta-information about the currently booted kernel of this node.",
      "properties": {
        "machine": {
          "description": "Hardware (architecture) type",
          "type": "string"
        },
        "release": {
          "description": "OS kernel release (e.g., \"6.8.0\")",
          "type": "string"
        },
        "sysname": {
          "description": "OS kernel name (e.g., \"Linux\")",
          "type": "string"
        },
        "version": {
          "description": "OS kernel version with build info",
          "type": "string"
        }
      },
      "type": "object"
    },
    "loadavg": {
      "description": "An array of load avg for 1, 5 and 15 minutes respectively.",
      "items": {
        "description": "The value of the load.",
        "type": "string"
      },
      "type": "array"
    },
    "memory": {
      "properties": {
        "available": {
          "description": "The available memory in bytes.",
          "type": "integer"
        },
        "free": {
          "description": "The free memory in bytes.",
          "type": "integer"
        },
        "total": {
          "description": "The total memory in bytes.",
          "type": "integer"
        },
        "used": {
          "description": "The used memory in bytes.",
          "type": "integer"
        }
      },
      "type": "object"
    },
    "pveversion": {
      "description": "The PVE version string.",
      "type": "string"
    },
    "rootfs": {
      "properties": {
        "avail": {
          "description": "The available bytes in the root filesystem.",
          "type": "integer"
        },
        "free": {
          "description": "The free bytes on the root filesystem.",
          "type": "integer"
        },
        "total": {
          "description": "The total size of the root filesystem in bytes.",
          "type": "integer"
        },
        "used": {
          "description": "The used bytes in the root filesystem.",
          "type": "integer"
        }
      },
      "type": "object"
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
    "/nodes/{node}",
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
  "description": "Read node status",
  "method": "GET",
  "name": "status",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Audit"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "additionalProperties": 1,
    "properties": {
      "boot-info": {
        "description": "Meta-information about the boot mode.",
        "properties": {
          "mode": {
            "description": "Through which firmware the system got booted.",
            "enum": [
              "efi",
              "legacy-bios"
            ],
            "type": "string"
          },
          "secureboot": {
            "description": "System is booted in secure mode, only applicable for the \"efi\" mode.",
            "optional": 1,
            "type": "boolean"
          }
        },
        "type": "object"
      },
      "cpu": {
        "description": "The current cpu usage.",
        "type": "number"
      },
      "cpuinfo": {
        "properties": {
          "cores": {
            "description": "The number of physical cores of the CPU.",
            "type": "integer"
          },
          "cpus": {
            "description": "The number of logical threads of the CPU.",
            "type": "integer"
          },
          "model": {
            "description": "The CPU model",
            "type": "string"
          },
          "sockets": {
            "description": "The number of logical threads of the CPU.",
            "type": "integer"
          }
        },
        "type": "object"
      },
      "current-kernel": {
        "description": "Meta-information about the currently booted kernel of this node.",
        "properties": {
          "machine": {
            "description": "Hardware (architecture) type",
            "type": "string"
          },
          "release": {
            "description": "OS kernel release (e.g., \"6.8.0\")",
            "type": "string"
          },
          "sysname": {
            "description": "OS kernel name (e.g., \"Linux\")",
            "type": "string"
          },
          "version": {
            "description": "OS kernel version with build info",
            "type": "string"
          }
        },
        "type": "object"
      },
      "loadavg": {
        "description": "An array of load avg for 1, 5 and 15 minutes respectively.",
        "items": {
          "description": "The value of the load.",
          "type": "string"
        },
        "type": "array"
      },
      "memory": {
        "properties": {
          "available": {
            "description": "The available memory in bytes.",
            "type": "integer"
          },
          "free": {
            "description": "The free memory in bytes.",
            "type": "integer"
          },
          "total": {
            "description": "The total memory in bytes.",
            "type": "integer"
          },
          "used": {
            "description": "The used memory in bytes.",
            "type": "integer"
          }
        },
        "type": "object"
      },
      "pveversion": {
        "description": "The PVE version string.",
        "type": "string"
      },
      "rootfs": {
        "properties": {
          "avail": {
            "description": "The available bytes in the root filesystem.",
            "type": "integer"
          },
          "free": {
            "description": "The free bytes on the root filesystem.",
            "type": "integer"
          },
          "total": {
            "description": "The total size of the root filesystem in bytes.",
            "type": "integer"
          },
          "used": {
            "description": "The used bytes in the root filesystem.",
            "type": "integer"
          }
        },
        "type": "object"
      }
    },
    "type": "object"
  }
}
```
