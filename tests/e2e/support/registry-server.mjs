// Serves the image catalogue fixture to the panel, so catalogue specs never
// depend on the live CDN. Started by Playwright's `webServer`.
import { readFile } from 'node:fs/promises'
import { createServer } from 'node:http'

const port = Number(process.env.E2E_REGISTRY_PORT ?? 4174)
const fixture = new URL('../fixtures/registry.json', import.meta.url)

createServer(async (request, response) => {
    if (request.url !== '/registry.json') {
        response.writeHead(404).end()
        return
    }

    response
        .writeHead(200, { 'Content-Type': 'application/json' })
        .end(await readFile(fixture))
}).listen(port, '127.0.0.1')
