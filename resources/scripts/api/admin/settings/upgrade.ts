import useSWR from 'swr'

import http from '@/api/http'

export interface AddressPoolSplit {
    pool: string
    blocks: { mac: string | null; addresses: number }[]
}

export interface UpgradeItem {
    key: string
    title: string
    /** `dropped`: not carried over. `changed`: carried over, but changed. */
    effect: 'dropped' | 'changed'
    affected: boolean
    count: number
    summary: string
    action: string | null
    /** How each address pool is split into address blocks, one per MAC. */
    splits: AddressPoolSplit[]
}

export const getUpgradeCheck = async (): Promise<UpgradeItem[]> => {
    const { data } = await http.get('/api/admin/settings/upgrade')

    return data.data.items
}

export const useUpgradeCheckSWR = () =>
    useSWR<UpgradeItem[]>('admin:settings:upgrade', getUpgradeCheck)
