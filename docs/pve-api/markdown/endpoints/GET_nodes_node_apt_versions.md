# GET /nodes/{node}/apt/versions

Get package information for important Proxmox packages.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "Arch": {
        "description": "Package Architecture.",
        "enum": [
          "armhf",
          "arm64",
          "amd64",
          "ppc64el",
          "risc64",
          "s390x",
          "all"
        ],
        "type": "string"
      },
      "CurrentState": {
        "description": "Current state of the package installed on the system.",
        "enum": [
          "Installed",
          "NotInstalled",
          "UnPacked",
          "HalfConfigured",
          "HalfInstalled",
          "ConfigFiles"
        ],
        "type": "string"
      },
      "Description": {
        "description": "Package description.",
        "type": "string"
      },
      "ManagerVersion": {
        "description": "Version of the currently running pve-manager API server.",
        "optional": 1,
        "type": "string"
      },
      "NotifyStatus": {
        "description": "Version for which PVE has already sent an update notification for.",
        "optional": 1,
        "type": "string"
      },
      "OldVersion": {
        "description": "Old version currently installed.",
        "optional": 1,
        "type": "string"
      },
      "Origin": {
        "description": "Package origin, e.g., 'Proxmox' or 'Debian'.",
        "type": "string"
      },
      "Package": {
        "description": "Package name.",
        "type": "string"
      },
      "Priority": {
        "description": "Package priority.",
        "type": "string"
      },
      "RunningKernel": {
        "description": "Kernel release, only for package 'proxmox-ve'.",
        "optional": 1,
        "type": "string"
      },
      "Section": {
        "description": "Package section.",
        "type": "string"
      },
      "Title": {
        "description": "Package title.",
        "type": "string"
      },
      "Version": {
        "description": "New version to be updated to.",
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
  "description": "Get package information for important Proxmox packages.",
  "method": "GET",
  "name": "versions",
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
    "items": {
      "properties": {
        "Arch": {
          "description": "Package Architecture.",
          "enum": [
            "armhf",
            "arm64",
            "amd64",
            "ppc64el",
            "risc64",
            "s390x",
            "all"
          ],
          "type": "string"
        },
        "CurrentState": {
          "description": "Current state of the package installed on the system.",
          "enum": [
            "Installed",
            "NotInstalled",
            "UnPacked",
            "HalfConfigured",
            "HalfInstalled",
            "ConfigFiles"
          ],
          "type": "string"
        },
        "Description": {
          "description": "Package description.",
          "type": "string"
        },
        "ManagerVersion": {
          "description": "Version of the currently running pve-manager API server.",
          "optional": 1,
          "type": "string"
        },
        "NotifyStatus": {
          "description": "Version for which PVE has already sent an update notification for.",
          "optional": 1,
          "type": "string"
        },
        "OldVersion": {
          "description": "Old version currently installed.",
          "optional": 1,
          "type": "string"
        },
        "Origin": {
          "description": "Package origin, e.g., 'Proxmox' or 'Debian'.",
          "type": "string"
        },
        "Package": {
          "description": "Package name.",
          "type": "string"
        },
        "Priority": {
          "description": "Package priority.",
          "type": "string"
        },
        "RunningKernel": {
          "description": "Kernel release, only for package 'proxmox-ve'.",
          "optional": 1,
          "type": "string"
        },
        "Section": {
          "description": "Package section.",
          "type": "string"
        },
        "Title": {
          "description": "Package title.",
          "type": "string"
        },
        "Version": {
          "description": "New version to be updated to.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
