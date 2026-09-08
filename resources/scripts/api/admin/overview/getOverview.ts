import http from '@/api/http'

export interface DashboardMetric {
    allocated: number
    total: number
    percent: number
}

export interface DashboardSummary {
    servers: number
    nodes: number
    users: number
    locations: number
    failedServers: number
}

export interface DashboardServers {
    total: number
    ready: number
    installing: number
    suspended: number
    restoring: number
    deleting: number
    failed: number
    statuses: Record<string, number>
}

export interface DashboardAddresses {
    pools: number
    total: number
    assigned: number
    available: number
    percent: number
}

export interface DashboardBackups {
    total: number
    successful: number
    pending: number
    failed: number
}

export interface DashboardIsos {
    total: number
    successful: number
    pending: number
}

/**
 * One record behind an attention row. `id` is the route key its group's
 * destination takes -- a server id for the server groups, a server's short uuid
 * for a backup, since the backups tab is where a backup actually lives.
 */
export interface AttentionSubject {
    id: string
    label: string
    detail: string | null
}

export interface DashboardAttention {
    failedServers: AttentionSubject[]
    failedBackups: AttentionSubject[]
    suspendedServers: AttentionSubject[]
}

export interface DashboardNode {
    id: number
    name: string
    cluster: string
    fqdn: string
    servers: number
    memory: DashboardMetric
    disk: DashboardMetric
}

export interface DashboardOverview {
    generatedAt: string
    summary: DashboardSummary
    servers: DashboardServers
    capacity: {
        memory: DashboardMetric
        disk: DashboardMetric
    }
    addresses: DashboardAddresses
    backups: DashboardBackups
    isos: DashboardIsos
    attention: DashboardAttention
    nodes: DashboardNode[]
}

const rawSubjects = (data: any): AttentionSubject[] =>
    (data ?? []).map((subject: any) => ({
        id: String(subject.id),
        label: subject.label,
        detail: subject.detail ?? null,
    }))

const rawMetric = (data: any): DashboardMetric => ({
    allocated: data.allocated,
    total: data.total,
    percent: data.percent,
})

export const rawDataToOverview = (data: any): DashboardOverview => ({
    generatedAt: data.generated_at,
    summary: {
        servers: data.summary.servers,
        nodes: data.summary.nodes,
        users: data.summary.users,
        locations: data.summary.locations,
        failedServers: data.summary.failed_servers,
    },
    servers: data.servers,
    capacity: {
        memory: rawMetric(data.capacity.memory),
        disk: rawMetric(data.capacity.disk),
    },
    addresses: data.addresses,
    backups: data.backups,
    isos: data.isos,
    attention: {
        failedServers: rawSubjects(data.attention?.failed_servers),
        failedBackups: rawSubjects(data.attention?.failed_backups),
        suspendedServers: rawSubjects(data.attention?.suspended_servers),
    },
    nodes: data.nodes.map((node: any) => ({
        id: node.id,
        name: node.name,
        cluster: node.cluster,
        fqdn: node.fqdn,
        servers: node.servers,
        memory: rawMetric(node.memory),
        disk: rawMetric(node.disk),
    })),
})

const getOverview = async (): Promise<DashboardOverview> => {
    const { data } = await http.get('/api/admin/overview')

    return rawDataToOverview(data.data)
}

export default getOverview
