import { ServerResources } from '@/types/server'

export const rawDataToServerResources = (data: any): ServerResources => ({
    usedBytes: data.usedBytes,
    totalBytes: data.totalBytes,
})
