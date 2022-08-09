export interface Node {
    id: number
    name: string
    cluster: string
    hostname: string
    port: number
    latency?: number
    last_pinged?: string
    created_at: string
    updated_at?: string
}