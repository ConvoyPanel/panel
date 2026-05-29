# GET /version

API version details, including some parts of the global datacenter config.

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "properties": {
    "console": {
      "description": "The default console viewer to use.",
      "enum": [
        "applet",
        "vv",
        "html5",
        "xtermjs"
      ],
      "optional": 1,
      "type": "string"
    },
    "release": {
      "description": "The current Proxmox VE point release in `x.y` format.",
      "type": "string"
    },
    "repoid": {
      "description": "The short git revision from which this version was build.",
      "pattern": "[0-9a-fA-F]{8,64}",
      "type": "string"
    },
    "version": {
      "description": "The full pve-manager package version of this node.",
      "type": "string"
    }
  },
  "type": "object"
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
  "description": "API version details, including some parts of the global datacenter config.",
  "method": "GET",
  "name": "version",
  "parameters": {
    "additionalProperties": 0
  },
  "permissions": {
    "user": "all"
  },
  "returns": {
    "properties": {
      "console": {
        "description": "The default console viewer to use.",
        "enum": [
          "applet",
          "vv",
          "html5",
          "xtermjs"
        ],
        "optional": 1,
        "type": "string"
      },
      "release": {
        "description": "The current Proxmox VE point release in `x.y` format.",
        "type": "string"
      },
      "repoid": {
        "description": "The short git revision from which this version was build.",
        "pattern": "[0-9a-fA-F]{8,64}",
        "type": "string"
      },
      "version": {
        "description": "The full pve-manager package version of this node.",
        "type": "string"
      }
    },
    "type": "object"
  }
}
```
