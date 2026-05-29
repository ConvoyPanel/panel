# GET /access/permissions

Retrieve effective permissions of given user/token.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| path | string | no | Only dump this specific path, not the whole tree. |
| userid | string | no | User ID or full API token ID |

## Returns

```json
{
  "description": "Map of \"path\" => (Map of \"privilege\" => \"propagate boolean\").",
  "type": "object"
}
```

## Permissions

```json
{
  "description": "Each user/token is allowed to dump their own permissions (or that of owned tokens). A user can dump the permissions of another user or their tokens if they have 'Sys.Audit' permission on /access.",
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Retrieve effective permissions of given user/token.",
  "method": "GET",
  "name": "permissions",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "path": {
        "description": "Only dump this specific path, not the whole tree.",
        "optional": 1,
        "type": "string",
        "typetext": "<string>"
      },
      "userid": {
        "description": "User ID or full API token ID",
        "optional": 1,
        "pattern": "(?^:^(?^:[^\\s:/]+)\\@(?^:[A-Za-z][A-Za-z0-9\\.\\-_]+)(?:!(?^:[A-Za-z][A-Za-z0-9\\.\\-_]+))?$)",
        "type": "string"
      }
    }
  },
  "permissions": {
    "description": "Each user/token is allowed to dump their own permissions (or that of owned tokens). A user can dump the permissions of another user or their tokens if they have 'Sys.Audit' permission on /access.",
    "user": "all"
  },
  "returns": {
    "description": "Map of \"path\" => (Map of \"privilege\" => \"propagate boolean\").",
    "type": "object"
  }
}
```
