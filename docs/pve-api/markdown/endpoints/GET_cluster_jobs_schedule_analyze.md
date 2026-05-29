# GET /cluster/jobs/schedule-analyze

Returns a list of future schedule runtimes.

## Path parameters

None.

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| schedule | string | yes | Job schedule. The format is a subset of `systemd` calendar events. |
| iterations | integer | no | Number of event-iteration to simulate and return. |
| starttime | integer | no | UNIX timestamp to start the calculation from. Defaults to the current time. |

## Returns

```json
{
  "description": "An array of the next <iterations> events since <starttime>.",
  "items": {
    "properties": {
      "timestamp": {
        "description": "UNIX timestamp for the run.",
        "type": "integer"
      },
      "utc": {
        "description": "UTC timestamp for the run.",
        "type": "string"
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
  "user": "all"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Returns a list of future schedule runtimes.",
  "method": "GET",
  "name": "schedule-analyze",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "iterations": {
        "default": 10,
        "description": "Number of event-iteration to simulate and return.",
        "maximum": 100,
        "minimum": 1,
        "optional": 1,
        "type": "integer",
        "typetext": "<integer> (1 - 100)"
      },
      "schedule": {
        "description": "Job schedule. The format is a subset of `systemd` calendar events.",
        "format": "pve-calendar-event",
        "maxLength": 128,
        "type": "string",
        "typetext": "<string>"
      },
      "starttime": {
        "description": "UNIX timestamp to start the calculation from. Defaults to the current time.",
        "optional": 1,
        "type": "integer",
        "typetext": "<integer>"
      }
    }
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "description": "An array of the next <iterations> events since <starttime>.",
    "items": {
      "properties": {
        "timestamp": {
          "description": "UNIX timestamp for the run.",
          "type": "integer"
        },
        "utc": {
          "description": "UTC timestamp for the run.",
          "type": "string"
        }
      },
      "type": "object"
    },
    "type": "array"
  }
}
```
