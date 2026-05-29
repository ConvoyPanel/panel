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

export interface DashboardNode {
    id: number
    name: string
    cluster: string
    fqdn: string
    servers: number
    memory: DashboardMetric
    disk: DashboardMetric
}

export type DashboardUpdateStatus =
    | 'ahead'
    | 'canary'
    | 'current'
    | 'outdated'
    | 'unavailable'
    | 'unknown'

export interface DashboardUpdate {
    status: DashboardUpdateStatus
    currentVersion: string
    latestVersion: string | null
    isOutdated: boolean
    releaseUrl: string | null
}

export interface DashboardOverview {
    generatedAt: string
    update: DashboardUpdate
    summary: DashboardSummary
    servers: DashboardServers
    capacity: {
        memory: DashboardMetric
        disk: DashboardMetric
    }
    addresses: DashboardAddresses
    backups: DashboardBackups
    isos: DashboardIsos
    nodes: DashboardNode[]
}

const rawMetric = (data: any): DashboardMetric => ({
    allocated: data.allocated,
    total: data.total,
    percent: data.percent,
})

export const rawDataToOverview = (data: any): DashboardOverview => ({
    generatedAt: data.generated_at,
    update: {
        status: data.update.status,
        currentVersion: data.update.current_version,
        latestVersion: data.update.latest_version,
        isOutdated: data.update.is_outdated,
        releaseUrl: data.update.release_url,
    },
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
