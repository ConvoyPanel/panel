# GET /cluster/nextid

Get next free VMID. Pass a VMID to assert that its free (at time of check).

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| vmid | integer | no | The (unique) ID of the VM. |

## Returns

```json
{
  "description": "The next free VMID.",
  "type": "integer"
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
  "description": "Get next free VMID. Pass a VMID to assert that its free (at time of check).",
  "method": "GET",
  "name": "nextid",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "description": "The next free VMID.",
    "type": "integer"
  }
}
```
