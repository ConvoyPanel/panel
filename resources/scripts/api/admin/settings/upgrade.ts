import useSWR from 'swr'

import http from '@/api/http'

export interface UpgradeItem {
    key: string
    title: string
    /** `dropped`: not carried over. `changed`: carried over, but changed. */
    effect: 'dropped' | 'changed'
    affected: boolean
    count: number
    summary: string
    action: string | null
    details: string[]
}

export const getUpgradeCheck = async (): Promise<UpgradeItem[]> => {
    const { data } = await http.get('/api/admin/settings/upgrade')

    return data.data.items
}

export const useUpgradeCheckSWR = () =>
    useSWR<UpgradeItem[]>('admin:settings:upgrade', getUpgradeCheck)
