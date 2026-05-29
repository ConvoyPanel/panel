# GET /nodes/{node}/qemu/{vmid}/firewall/ipset/{name}

List IPSet content

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| name | string | yes | IP set name. |
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "cidr": {
        "type": "string"
      },
      "comment": {
        "optional": 1,
        "type": "string"
      },
      "digest": {
        "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
        "maxLength": 64,
        "optional": 0,
        "type": "string"
      },
      "nomatch": {
        "optional": 1,
        "type": "boolean"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{cidr}",
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
    "/vms/{vmid}",
    [
      "VM.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "List IPSet content",
  "method": "GET",
  "name": "get_ipset",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "name": {
        "description": "IP set name.",
        "maxLength": 64,
        "minLength": 2,
        "pattern": "[A-Za-z][A-Za-z0-9\\-\\_]+",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
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
        "VM.Audit"
      ]
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "cidr": {
          "type": "string"
        },
        "comment": {
          "optional": 1,
          "type": "string"
        },
        "digest": {
          "description": "Prevent changes if current configuration file has a different digest. This can be used to prevent concurrent modifications.",
          "maxLength": 64,
          "optional": 0,
          "type": "string"
        },
        "nomatch": {
          "optional": 1,
          "type": "boolean"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{cidr}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
