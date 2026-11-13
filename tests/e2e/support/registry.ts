/** Where `registry-server.mjs` serves `fixtures/registry.json`. Point IMAGE_REGISTRY_URL here. */
export const REGISTRY_PORT = Number(process.env.E2E_REGISTRY_PORT ?? 4174)

export const REGISTRY_URL = `http://127.0.0.1:${REGISTRY_PORT}/registry.json`
