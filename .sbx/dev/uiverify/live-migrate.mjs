/*
 * Live end-to-end: adopt a real guest off one Proxmox node, then migrate it to
 * the other over Anchor. Drives the panel's own HTTP API with a real session,
 * so nothing here is mocked.
 */
import { BASE, launch, newContext, login } from '/opt/sbx-e2e/browser.mjs'

// Either direction: the reverse run is the same code with these swapped.
const SOURCE_NODE = Number(process.env.SOURCE_NODE ?? 3)
const TARGET_NODE = Number(process.env.TARGET_NODE ?? 4)
const VMID = 9200
const KNOWN_SERVER = process.env.SERVER_UUID || null

const browser = await launch()
const ctx = await newContext(browser)
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })

const api = async (method, path, body) =>
    page.evaluate(
        async ([m, p, b]) => {
            const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) ?? [])[1] ?? '')
            const res = await fetch(p, {
                method: m,
                headers: {
                    'X-XSRF-TOKEN': xsrf,
                    Accept: 'application/json',
                    ...(b ? { 'Content-Type': 'application/json' } : {}),
                },
                ...(b ? { body: JSON.stringify(b) } : {}),
            })
            const text = await res.text()

            return { status: res.status, body: text }
        },
        [method, path, body ?? null]
    )

console.log('== 0. what storages does the panel see on each node? ==')
for (const n of [SOURCE_NODE, TARGET_NODE]) {
    const live = await api('GET', `/api/admin/nodes/${n}/storages/proxmox`)
    console.log(`  node ${n} live:`, live.status, live.body.slice(0, 400))
    const known = await api('GET', `/api/admin/nodes/${n}/storages`)
    console.log(`  node ${n} known:`, known.status, known.body.slice(0, 300))
}

console.log('\n== 0b. persist the `local` storage on both nodes ==')
for (const n of [SOURCE_NODE, TARGET_NODE]) {
    // `size` is written in BYTES: StorageSizeCast stores mebibytes and reads
    // bytes back, so a value already in MiB is floored to 0 and fails min:1.
    const live2 = await api('GET', `/api/admin/nodes/${n}/storages/proxmox`)
    let bytes = 10 * 1024 ** 3
    try {
        const total = JSON.parse(live2.body).data.find(x => x.name === 'local')?.total
        if (total) bytes = total
    } catch {
        /* fall back to the default above */
    }

    const made = await api('POST', `/api/admin/nodes/${n}/storages`, { name: 'local', size: bytes })
    console.log(`  node ${n} create (${bytes} bytes):`, made.status, made.body.slice(0, 250))
}

console.log('\n== 1. is the guest visible as adoptable? ==')
const look = await api('GET', `/api/admin/nodes/${SOURCE_NODE}/adoptable-guests/${VMID}`)
console.log(look.status, look.body.slice(0, 600))

console.log('\n== 2. adopt it ==')
const adopt = await api('POST', `/api/admin/nodes/${SOURCE_NODE}/adoptable-guests/${VMID}`, { user_id: 2 })
console.log(adopt.status, adopt.body.slice(0, 800))

let serverUuid = null
try {
    const d = JSON.parse(adopt.body)
    serverUuid = d?.data?.uuid ?? d?.uuid ?? null
} catch {
    /* printed above */
}

// A second run refuses, correctly, because the guest is already adopted. Find
// the server that refusal is pointing at rather than treating it as a failure.
if (!serverUuid && KNOWN_SERVER) {
    serverUuid = KNOWN_SERVER
    console.log('  already adopted; using the known server uuid')
}

if (!serverUuid) {
    const list = await api('GET', '/api/admin/servers?per_page=100')
    try {
        const rows = JSON.parse(list.body)?.data ?? []
        serverUuid = rows.find(r => r.vmid === VMID)?.uuid ?? null
        console.log('  already adopted; reusing existing server')
    } catch {
        console.log('  could not list servers:', list.status, list.body.slice(0, 200))
    }
}

console.log('server uuid:', serverUuid)

if (!serverUuid) {
        process.exit(0)
}

console.log('\n== 3. migration plan ==')
const plan = await api('GET', `/api/admin/servers/${serverUuid}/migration`)
console.log(plan.status, plan.body.slice(0, 1200))

console.log('\n== 4. plan for the specific destination ==')
const planTo = await api('GET', `/api/admin/servers/${serverUuid}/migration/${TARGET_NODE}`)
console.log(planTo.status, planTo.body.slice(0, 1200))


console.log('\n== 5. COMMIT the migration ==')
const go = await api('POST', `/api/admin/servers/${serverUuid}/migration`, {
    node_id: TARGET_NODE,
    acknowledge_address_change: true,
})
console.log(go.status, go.body.slice(0, 1200))

await browser.close()
