# GET /cluster/backup-info/not-backed-up

Shows all guests which are not covered by any backup job.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "description": "Contains the guest objects.",
  "items": {
    "properties": {
      "name": {
        "description": "Name of the guest",
        "optional": 1,
        "type": "string"
      },
      "type": {
        "description": "Type of the guest.",
        "enum": [
          "qemu",
          "lxc"
        ],
        "type": "string"
      },
      "vmid": {
        "description": "VMID of the guest.",
        "type": "integer"
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
    "/",
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
  "description": "Shows all guests which are not covered by any backup job.",
  "method": "GET",
  "name": "get_guests_not_in_backup",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Audit"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "description": "Contains the guest objects.",
    "items": {
      "properties": {
        "name": {
          "description": "Name of the guest",
          "optional": 1,
          "type": "string"
        },
        "type": {
          "description": "Type of the guest.",
          "enum": [
            "qemu",
            "lxc"
          ],
          "type": "string"
        },
        "vmid": {
          "description": "VMID of the guest.",
          "type": "integer"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
