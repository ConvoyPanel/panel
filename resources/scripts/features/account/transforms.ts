import { Passkey } from '@/features/account/types.ts'

export const rawDataToPasskey = (data: any): Passkey => ({
    id: data.id,
    name: data.name,
    lastUsedAt: data.lastUsedAt ? new Date(data.lastUsedAt) : null,
    createdAt: new Date(data.createdAt),
})
