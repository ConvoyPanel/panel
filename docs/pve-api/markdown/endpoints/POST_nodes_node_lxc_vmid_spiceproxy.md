# POST /nodes/{node}/lxc/{vmid}/spiceproxy

Returns a SPICE configuration to connect to the CT.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
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
    "/vms/{vmid}",
    [
      "VM.Console"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Returns a SPICE configuration to connect to the CT.",
  "method": "POST",
  "name": "spiceproxy",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
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
        "VM.Console"
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
