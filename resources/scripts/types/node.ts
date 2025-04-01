import { PaginatedResult } from '@/utils/http.ts'

export interface Node {
    id: number
    locationId: number
    displayName: string
    name: string
    verifyTls: boolean
    fqdn: string
    port: number
    socketCount: number
    coreCount: number
    cpuCount: number
    memory: number
    memoryOverallocate: number
    memoryAllocated: number
    cotermId: number | null
    serversCount: number
}

export type PaginatedNodes = PaginatedResult<Node>

export enum ConnectionErrorType {
    TlsError = 'TLS_ERROR',
    ConnectionRefused = 'CONNECTION_REFUSED',
    Timeout = 'TIMEOUT',
    DnsError = 'DNS_ERROR',
    TokenInvalid = 'INVALID_TOKEN',
    TokenMissingPermissions = 'TOKEN_MISSING_PERMISSIONS',
    Other = 'OTHER',
}

export interface ConnectionResult {
    success: boolean
    errorMessage: string | null,
    errorCode: ConnectionErrorType | null
    data: NodeStatus | null
}

export interface NodeStatus {
    kernel: {
        build: string
        release: string
        os: string
        architecture: string
    }
    cpu: {
        socketCount: number
        coreCount: number
        cpuCount: number
        model: string
        flags: string
    }
    memory: {
        used: number
        free: number
        total: number
    }
    swap: {
        used: number
        free: number
        total: number
    }
    uptime: Duration
}