# DELETE /cluster/acme/plugins/{id}

Delete ACME plugin configuration.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Unique identifier for ACME plugin instance. |

## Request parameters

None.

## Returns

```json
{
  "type": "null"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/",
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
  "description": "Delete ACME plugin configuration.",
  "method": "DELETE",
  "name": "delete_plugin",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "Unique identifier for ACME plugin instance.",
        "format": "pve-configid",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/",
      [
        "Sys.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
