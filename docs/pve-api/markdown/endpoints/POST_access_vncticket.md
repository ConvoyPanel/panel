# POST /access/vncticket

verify VNC authentication ticket.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| authid | string | yes | UserId or token |
| path | string | yes | Verify ticket, and check if user have access 'privs' on 'path' |
| privs | string | yes | Verify ticket, and check if user have access 'privs' on 'path' |
| vncticket | string | yes | The VNC ticket. |
| port | integer | no | Verify that the ticket is valid for this port. |

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "description": "You need to pass valid credientials.",
  "user": "world"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "verify VNC authentication ticket.",
  "method": "POST",
  "name": "verify_vnc_ticket",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "authid": {
        "description": "UserId or token",
        "maxLength": 64,
        "type": "string",
        "typetext": "<string>"
      },
      "path": {
        "description": "Verify ticket, and check if user have access 'privs' on 'path'",
        "maxLength": 64,
        "type": "string",
        "typetext": "<string>"
      },
      "port": {
        "description": "Verify that the ticket is valid for this port.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      },
      "privs": {
        "description": "Verify ticket, and check if user have access 'privs' on 'path'",
        "format": "pve-priv-list",
        "maxLength": 64,
        "type": "string",
        "typetext": "<string>"
      },
      "vncticket": {
        "description": "The VNC ticket.",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "description": "You need to pass valid credientials.",
    "user": "world"
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
