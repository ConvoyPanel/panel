# POST /nodes/{node}/spiceshell

Creates a SPICE shell.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cmd | string | no | Run specific command or default to login (requires 'root@pam') |
| cmd-opts | string | no | Add parameters to a command. Encoded as null terminated strings. |
| proxy | string | no | SPICE proxy server. This can be used by the client to specify the proxy server. All nodes in a cluster runs 'spiceproxy', so it is up to the client to choose one. By default, we return the node where the VM is currently running. As reasonable setting is to use same node you use to connect to the API (This is window.location.hostname for the JS GUI). |

## Returns

```json
{
  "additionalProperties": 1,
  "description": "Returned values can be directly passed to the 'remote-viewer' application.",
  "properties": {
    "host": {
      "type": "string"
    },
    "password": {
      "type": "string"
    },
    "proxy": {
      "type": "string"
    },
    "tls-port": {
      "type": "integer"
    },
    "type": {
      "type": "string"
    }
  }
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/nodes/{node}",
    [
      "Sys.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Creates a SPICE shell.",
  "method": "POST",
  "name": "spiceshell",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cmd": {
        "default": "login",
        "description": "Run specific command or default to login (requires 'root@pam')",
        "enum": [
          "ceph_install",
          "login",
          "upgrade"
        ],
        "optional": 1,
        "type": "string"
      },
      "cmd-opts": {
        "default": "",
        "description": "Add parameters to a command. Encoded as null terminated strings.",
        "optional": 1,
        "requires": "cmd",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "proxy": {
        "description": "SPICE proxy server. This can be used by the client to specify the proxy server. All nodes in a cluster runs 'spiceproxy', so it is up to the client to choose one. By default, we return the node where the VM is currently running. As reasonable setting is to use same node you use to connect to the API (This is window.location.hostname for the JS GUI).",
        "format": "address",
        "optional": 1,
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
        "Sys.Console"
      ]
    ]
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "additionalProperties": 1,
    "description": "Returned values can be directly passed to the 'remote-viewer' application.",
    "properties": {
      "host": {
        "type": "string"
      },
      "password": {
        "type": "string"
      },
      "proxy": {
        "type": "string"
      },
      "tls-port": {
        "type": "integer"
      },
      "type": {
        "type": "string"
      }
    }
  }
}
```
