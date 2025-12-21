import { ServerResources } from '@/types/server'

export const rawDataToServerResources = (data: any): ServerResources => ({
    usedBytes: data.used_bytes,
    totalBytes: data.total_bytes,
})
