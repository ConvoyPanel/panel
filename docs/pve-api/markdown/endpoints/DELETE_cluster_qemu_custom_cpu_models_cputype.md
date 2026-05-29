# DELETE /cluster/qemu/custom-cpu-models/{cputype}

Delete a custom CPU model definition.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| cputype | string | yes | The custom model to delete. The 'custom-' prefix is optional. |

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
    "/mapping/cpu/{cputype}",
    [
      "Mapping.Modify"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Delete a custom CPU model definition.",
  "method": "DELETE",
  "name": "delete",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "cputype": {
        "description": "The custom model to delete. The 'custom-' prefix is optional.",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/mapping/cpu/{cputype}",
      [
        "Mapping.Modify"
      ]
    ]
  },
  "protected": 1,
  "returns": {
    "type": "null"
  }
}
```
