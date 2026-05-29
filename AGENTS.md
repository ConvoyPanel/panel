# AGENTS.md

## Proxmox VE API documentation

Generated Proxmox VE API docs are in:

- `docs/pve-api/llms.txt` - compact overview
- `docs/pve-api/search-index.json` - endpoint search index
- `docs/pve-api/endpoints.json` - normalized full endpoint data
- `docs/pve-api/markdown/endpoints/` - one Markdown page per endpoint
- `docs/pve-api/llms-full.txt` - full concatenated docs, use only when needed

When answering Proxmox VE API questions:

1. Read `docs/pve-api/llms.txt` first.
2. Use `search-index.json` or `endpoints.json` to find relevant endpoints.
3. Open the specific endpoint Markdown file for details.
4. Do not guess endpoint names from memory.
5. Do not call the Proxmox API; these docs are reference-only.