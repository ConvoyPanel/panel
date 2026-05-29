# GET /nodes/{node}/services/{service}

Directory index

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| service | string | yes | Service ID |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "subdir": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{subdir}",
      "rel": "child"
    }
  ],
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
  "description": "Directory index",
  "method": "GET",
  "name": "srvcmdidx",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "service": {
        "description": "Service ID",
        "enum": [
          "chrony",
          "corosync",
          "cron",
          "ksmtuned",
          "lxcfs",
          "postfix",
          "proxmox-firewall",
          "pve-cluster",
          "pve-firewall",
          "pve-ha-crm",
          "pve-ha-lrm",
          "pve-lxc-syscalld",
          "pvedaemon",
          "pvefw-logger",
          "pveproxy",
          "pvescheduler",
          "pvestatd",
          "qmeventd",
          "spiceproxy",
          "sshd",
          "syslog",
          "systemd-journald",
          "systemd-timesyncd"
        ],
        "type": "string"
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
  "returns": {
    "items": {
      "properties": {
        "subdir": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{subdir}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
