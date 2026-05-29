# GET /nodes/{node}/replication/{id}

Directory index.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| id | string | yes | Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'. |
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {},
    "type": "object"
  },
  "links": [
    {
      "href": "{name}",
      "rel": "child"
    }
  ],
  "type": "array"
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
  "description": "Directory index.",
  "method": "GET",
  "name": "index",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "id": {
        "description": "Replication Job ID. The ID is composed of a Guest ID and a job number, separated by a hyphen, i.e. '<GUEST>-<JOBNUM>'.",
        "format": "pve-replication-job-id",
        "pattern": "[1-9][0-9]{2,8}-\\d{1,9}",
        "type": "string"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "items": {
      "properties": {},
      "type": "object"
    },
    "links": [
      {
        "href": "{name}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
