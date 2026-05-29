# POST /nodes/{node}/services/{service}/start

Start service.

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
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/nodes/{node}",
    [
      "Sys.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Start service.",
  "method": "POST",
  "name": "service_start",
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
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
