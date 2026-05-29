# POST /nodes/{node}/execute

Execute multiple commands in order, root only.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| commands | string | yes | JSON encoded array of commands. |

## Returns

```json
{
  "items": {
    "properties": {},
    "type": "object"
  },
  "type": "array"
}
```

## Permissions

Not specified.

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Execute multiple commands in order, root only.",
  "method": "POST",
  "name": "execute",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "commands": {
        "description": "JSON encoded array of commands.",
        "format": "pve-command-batch",
        "type": "string",
        "typetext": "<string>",
        "verbose_description": "JSON encoded array of commands, where each command is an object with the following properties:\n  args:      <object>\n\t     A set of parameter names and their values.\n\n  method:    (GET|POST|PUT|DELETE)\n\t     A method related to the API endpoint (GET, POST etc.).\n\n  path:      <string>\n\t     A relative path to an API endpoint on this node.\n\n"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "items": {
      "properties": {},
      "type": "object"
    },
    "type": "array"
  }
}
```
