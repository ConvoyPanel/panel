# GET /nodes/{node}/apt/repositories

Get APT repository information.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |

## Request parameters

None.

## Returns

```json
{
  "description": "Result from parsing the APT repository files in /etc/apt/.",
  "properties": {
    "digest": {
      "description": "Common digest of all files.",
      "type": "string"
    },
    "errors": {
      "description": "List of problematic repository files.",
      "items": {
        "properties": {
          "error": {
            "description": "The error message",
            "type": "string"
          },
          "path": {
            "description": "Path to the problematic file.",
            "type": "string"
          }
        },
        "type": "object"
      },
      "type": "array"
    },
    "files": {
      "description": "List of parsed repository files.",
      "items": {
        "properties": {
          "digest": {
            "description": "Digest of the file as bytes.",
            "items": {
              "type": "integer"
            },
            "type": "array"
          },
          "file-type": {
            "description": "Format of the file.",
            "enum": [
              "list",
              "sources"
            ],
            "type": "string"
          },
          "path": {
            "description": "Path to the problematic file.",
            "type": "string"
          },
          "repositories": {
            "description": "The parsed repositories.",
            "items": {
              "properties": {
                "Comment": {
                  "description": "Associated comment",
                  "optional": 1,
                  "type": "string"
                },
                "Components": {
                  "description": "List of repository components",
                  "items": {
                    "type": "string"
                  },
                  "optional": 1,
                  "type": "array"
                },
                "Enabled": {
                  "description": "Whether the repository is enabled or not",
                  "type": "boolean"
                },
                "FileType": {
                  "description": "Format of the defining file.",
                  "enum": [
                    "list",
                    "sources"
                  ],
                  "type": "string"
                },
                "Options": {
                  "description": "Additional options",
                  "items": {
                    "properties": {
                      "Key": {
                        "type": "string"
                      },
                      "Values": {
                        "items": {
                          "type": "string"
                        },
                        "type": "array"
                      }
                    },
                    "type": "object"
                  },
                  "optional": 1,
                  "type": "array"
                },
                "Suites": {
                  "description": "List of package distribuitions",
                  "items": {
                    "type": "string"
                  },
                  "type": "array"
                },
                "Types": {
                  "description": "List of package types.",
                  "items": {
                    "enum": [
                      "deb",
                      "deb-src"
                    ],
                    "type": "string"
                  },
                  "type": "array"
                },
                "URIs": {
                  "description": "List of repository URIs.",
                  "items": {
                    "type": "string"
                  },
                  "type": "array"
                }
              },
              "type": "object"
            },
            "type": "array"
          }
        },
        "type": "object"
      },
      "type": "array"
    },
    "infos": {
      "description": "Additional information/warnings for APT repositories.",
      "items": {
        "properties": {
          "index": {
            "description": "Index of the associated repository within the file.",
            "type": "string"
          },
          "kind": {
            "description": "Kind of the information (e.g. warning).",
            "type": "string"
          },
          "message": {
            "description": "Information message.",
            "type": "string"
          },
          "path": {
            "description": "Path to the associated file.",
            "type": "string"
          },
          "property": {
            "description": "Property from which the info originates.",
            "optional": 1,
            "type": "string"
          }
        },
        "type": "object"
      },
      "type": "array"
    },
    "standard-repos": {
      "description": "List of standard repositories and their configuration status",
      "items": {
        "properties": {
          "handle": {
            "description": "Handle to identify the repository.",
            "type": "string"
          },
          "name": {
            "description": "Full name of the repository.",
            "type": "string"
          },
          "status": {
            "description": "Indicating enabled/disabled status, if the repository is configured.",
            "optional": 1,
            "type": "boolean"
          }
        },
        "type": "object"
      },
      "type": "array"
    }
  },
  "type": "object"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/nodes/{node}",
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
  "description": "Get APT repository information.",
  "method": "GET",
  "name": "repositories",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/nodes/{node}",
      [
        "Sys.Audit"
      ]
    ]
  },
  "proxyto": "node",
  "returns": {
    "description": "Result from parsing the APT repository files in /etc/apt/.",
    "properties": {
      "digest": {
        "description": "Common digest of all files.",
        "type": "string"
      },
      "errors": {
        "description": "List of problematic repository files.",
        "items": {
          "properties": {
            "error": {
              "description": "The error message",
              "type": "string"
            },
            "path": {
              "description": "Path to the problematic file.",
              "type": "string"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "files": {
        "description": "List of parsed repository files.",
        "items": {
          "properties": {
            "digest": {
              "description": "Digest of the file as bytes.",
              "items": {
                "type": "integer"
              },
              "type": "array"
            },
            "file-type": {
              "description": "Format of the file.",
              "enum": [
                "list",
                "sources"
              ],
              "type": "string"
            },
            "path": {
              "description": "Path to the problematic file.",
              "type": "string"
            },
            "repositories": {
              "description": "The parsed repositories.",
              "items": {
                "properties": {
                  "Comment": {
                    "description": "Associated comment",
                    "optional": 1,
                    "type": "string"
                  },
                  "Components": {
                    "description": "List of repository components",
                    "items": {
                      "type": "string"
                    },
                    "optional": 1,
                    "type": "array"
                  },
                  "Enabled": {
                    "description": "Whether the repository is enabled or not",
                    "type": "boolean"
                  },
                  "FileType": {
                    "description": "Format of the defining file.",
                    "enum": [
                      "list",
                      "sources"
                    ],
                    "type": "string"
                  },
                  "Options": {
                    "description": "Additional options",
                    "items": {
                      "properties": {
                        "Key": {
                          "type": "string"
                        },
                        "Values": {
                          "items": {
                            "type": "string"
                          },
                          "type": "array"
                        }
                      },
                      "type": "object"
                    },
                    "optional": 1,
                    "type": "array"
                  },
                  "Suites": {
                    "description": "List of package distribuitions",
                    "items": {
                      "type": "string"
                    },
                    "type": "array"
                  },
                  "Types": {
                    "description": "List of package types.",
                    "items": {
                      "enum": [
                        "deb",
                        "deb-src"
                      ],
                      "type": "string"
                    },
                    "type": "array"
                  },
                  "URIs": {
                    "description": "List of repository URIs.",
                    "items": {
                      "type": "string"
                    },
                    "type": "array"
                  }
                },
                "type": "object"
              },
              "type": "array"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "infos": {
        "description": "Additional information/warnings for APT repositories.",
        "items": {
          "properties": {
            "index": {
              "description": "Index of the associated repository within the file.",
              "type": "string"
            },
            "kind": {
              "description": "Kind of the information (e.g. warning).",
              "type": "string"
            },
            "message": {
              "description": "Information message.",
              "type": "string"
            },
            "path": {
              "description": "Path to the associated file.",
              "type": "string"
            },
            "property": {
              "description": "Property from which the info originates.",
              "optional": 1,
              "type": "string"
            }
          },
          "type": "object"
        },
        "type": "array"
      },
      "standard-repos": {
        "description": "List of standard repositories and their configuration status",
        "items": {
          "properties": {
            "handle": {
              "description": "Handle to identify the repository.",
              "type": "string"
            },
            "name": {
              "description": "Full name of the repository.",
              "type": "string"
            },
            "status": {
              "description": "Indicating enabled/disabled status, if the repository is configured.",
              "optional": 1,
              "type": "boolean"
            }
          },
          "type": "object"
        },
        "type": "array"
      }
    },
    "type": "object"
  }
}
```
