# GET /cluster/jobs/realm-sync/{id}

Read realm-sync job definition.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes |  |

## Request parameters

None.

## Returns

```json
{
  "type": "object"
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
  "description": "Read realm-sync job definition.",
  "method": "GET",
  "name": "read_job",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
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
        "Sys.Audit"
      ]
    ]
  },
  "returns": {
    "type": "object"
  }
}
```
