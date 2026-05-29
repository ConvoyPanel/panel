# GET /cluster/sdn/fabrics

SDN Fabrics Index

## Path parameters

None.

## Request parameters

None.

## Returns

```json
{
  "items": {
    "properties": {
      "subdir": {
        "type": "string"
      }
    },
    "type": "object"
  },
  "links": [
    {
      "href": "{subdir}",
      "rel": "child"
    }
  ],
  "type": "array"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/sdn/fabrics",
    [
      "SDN.Audit"
    ]
  ]
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "SDN Fabrics Index",
  "method": "GET",
  "name": "index",
  "parameters": {},
  "permissions": {
    "check": [
      "perm",
      "/sdn/fabrics",
      [
        "SDN.Audit"
      ]
    ]
  },
  "returns": {
    "items": {
      "properties": {
        "subdir": {
          "type": "string"
        }
      },
      "type": "object"
    },
    "links": [
      {
        "href": "{subdir}",
        "rel": "child"
      }
    ],
    "type": "array"
  }
}
```
