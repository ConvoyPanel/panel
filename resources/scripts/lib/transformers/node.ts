import {
    ConnectionErrorType,
    ConnectionResult,
    Node,
    NodeStatus,
} from '@/types/node.ts'
import { intervalToDuration } from 'date-fns'

export const rawDataToNode = (data: any): Node => data as Node

export const mapConnectionErrorType = (
    errorCode: string
): ConnectionErrorType => {
    switch (errorCode) {
        case 'tls_error':
            return ConnectionErrorType.TlsError
        case 'connection_refused':
            return ConnectionErrorType.ConnectionRefused
        case 'timeout':
            return ConnectionErrorType.Timeout
        case 'dns_error':
            return ConnectionErrorType.DnsError
        case 'token_invalid':
            return ConnectionErrorType.TokenInvalid
        case 'token_missing_permissions':
            return ConnectionErrorType.TokenMissingPermissions
        default:
            return ConnectionErrorType.Other
    }
}

export const rawDataToNodeStatus = (data: any): NodeStatus => ({
    kernel: {
        build: data.kernel.build,
        release: data.kernel.release,
        os: data.kernel.os,
        architecture: data.kernel.architecture,
    },
    cpu: {
        socketCount: data.cpu.socketCount,
        coreCount: data.cpu.coreCount,
        cpuCount: data.cpu.cpuCount,
        model: data.cpu.model,
        flags: data.cpu.flags,
    },
    memory: {
        used: data.memory.used,
        free: data.memory.free,
        total: data.memory.total,
    },
    swap: {
        used: data.swap.used,
        free: data.swap.free,
        total: data.swap.total,
    },
    uptime: intervalToDuration({ start: 0, end: data.uptime }),
})

export const rawDataToConnectionResult = (data: any): ConnectionResult => {
    return {
        success: data.success,
        errorMessage: data.errorMessage || null,
        errorCode: data.errorCode
            ? mapConnectionErrorType(data.errorCode)
            : null,
        data: data.data ? rawDataToNodeStatus(data.data) : null,
    }
}
