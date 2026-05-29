# DELETE /cluster/backup/{id}

Delete vzdump backup job definition.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | The job ID. |

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
  "description": "Delete vzdump backup job definition.",
  "method": "DELETE",
  "name": "delete_job",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "The job ID.",
        "maxLength": 50,
        "pattern": "\\S+",
        "type": "string"
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
