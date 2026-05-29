# GET /nodes/{node}/lxc/{vmid}/firewall/refs

Lists possible IPSet/Alias reference which are allowed in source/dest properties.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| type | string | no | Only list references of specified type. |

## Returns

```json
{
  "items": {
    "properties": {
      "comment": {
        "optional": 1,
        "type": "string"
      },
      "name": {
        "type": "string"
      },
      "ref": {
        "type": "string"
      },
      "scope": {
        "type": "string"
      },
      "type": {
        "enum": [
          "alias",
          "ipset"
        ],
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
  "description": "Lists possible IPSet/Alias reference which are allowed in source/dest properties.",
  "method": "GET",
  "name": "refs",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "type": {
        "description": "Only list references of specified type.",
        "enum": [
          "alias",
          "ipset"
        ],
        "optional": 1,
        "type": "string"
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
        "comment": {
          "optional": 1,
          "type": "string"
        },
        "name": {
          "type": "string"
        },
        "ref": {
          "type": "string"
        },
        "scope": {
          "type": "string"
        },
        "type": {
          "enum": [
            "alias",
            "ipset"
          ],
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
