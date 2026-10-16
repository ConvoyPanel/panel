/*
 * Drive the chunked upload endpoint directly.
 *
 * The point of this endpoint is that no single request carries the file, so the
 * things worth proving are the ones a happy-path UI click would not show: that
 * it really takes more than one chunk, that the server's offset is authoritative
 * after an interrupted chunk, that a resumed upload still hashes correctly, and
 * that a wrong offset is refused rather than silently corrupting the file.
 */
import { BASE, launch, newContext, login } from '/opt/sbx-e2e/browser.mjs'
import { execFileSync } from 'node:child_process'
import { createHash } from 'node:crypto'
import { readFileSync } from 'node:fs'

const SRC = '/tmp/upload-test.qcow2'
const MB = 1024 * 1024

// A real qcow2, so the inspector has something truthful to read.
execFileSync('qemu-img', ['create', '-f', 'qcow2', SRC, '64M'], { stdio: 'ignore' })
execFileSync('bash', ['-c', `dd if=/dev/urandom bs=1M count=40 >> ${SRC} 2>/dev/null`])

const bytes = readFileSync(SRC)
const sha = createHash('sha256').update(bytes).digest('hex')

console.log('source: ' + bytes.length + ' bytes, sha256 ' + sha.slice(0, 16) + '...')

const browser = await launch()
const ctx = await newContext(browser)
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })

// Run the whole exchange inside the page so the session cookie and CSRF token
// are the real ones the app uses.
const result = await page.evaluate(
    async ({ b64, total, sha }) => {
        const bin = Uint8Array.from(atob(b64), c => c.charCodeAt(0))
        const xsrf = decodeURIComponent(
            (document.cookie.match(/XSRF-TOKEN=([^;]+)/) ?? [])[1] ?? ''
        )
        const headers = { 'X-XSRF-TOKEN': xsrf, Accept: 'application/json' }
        const log = []

        const open = await fetch('/api/admin/images/uploads', {
            method: 'POST',
            headers: { ...headers, 'Content-Type': 'application/json' },
            body: JSON.stringify({ file_name: 'upload-test.qcow2', size: total, sha256: sha }),
        })

        if (!open.ok) return { step: 'open', status: open.status, body: (await open.text()).slice(0, 300) }

        const opened = await open.json()
        log.push('opened chunk_size=' + opened.chunk_size)

        const chunk = opened.chunk_size
        let offset = 0
        let interrupted = false

        while (offset < total) {
            const end = Math.min(offset + chunk, total)
            let slice = bin.slice(offset, end)

            // Once, part-way through, send a short body to imitate a dropped
            // connection, then trust the server's offset rather than our own.
            if (!interrupted && offset > 0) {
                interrupted = true
                slice = slice.slice(0, Math.floor(slice.length / 3))
                log.push('sending a deliberately short chunk at ' + offset)
            }

            const res = await fetch('/api/admin/images/uploads/' + opened.uuid, {
                method: 'PUT',
                headers: { ...headers, 'Upload-Offset': String(offset), 'Content-Type': 'application/offset+octet-stream' },
                body: slice,
            })

            if (!res.ok) return { step: 'append', offset, status: res.status, body: (await res.text()).slice(0, 300), log }

            const state = await res.json()
            log.push('offset ' + offset + ' -> ' + state.offset)
            offset = state.offset
        }

        // A stale offset must be refused, not appended.
        const stale = await fetch('/api/admin/images/uploads/' + opened.uuid, {
            method: 'PUT',
            headers: { ...headers, 'Upload-Offset': '0', 'Content-Type': 'application/offset+octet-stream' },
            body: bin.slice(0, 1024),
        })
        log.push('stale offset rejected with ' + stale.status)

        const fin = await fetch('/api/admin/images/uploads/' + opened.uuid + '/finalize', {
            method: 'POST',
            headers,
        })

        return {
            step: 'done',
            chunkSize: opened.chunk_size,
            finalStatus: fin.status,
            described: fin.ok ? await fin.json() : (await fin.text()).slice(0, 300),
            staleStatus: stale.status,
            log,
        }
    },
    { b64: bytes.toString('base64'), total: bytes.length, sha }
)

console.log(JSON.stringify(result, null, 1))
console.log('\nexpected sha256:', sha)

await browser.close()
