import http, {
    FractalResponseData,
    PaginatedResult,
    getPaginationSet,
} from '@/api/http'

interface QueryParams {
    query?: string
    page?: number
    perPage?: number
}

export interface Backup {
    uuid: string
    isSuccessful: boolean
    isLocked: boolean
    name: string
    size: number
    completedAt?: Date
    createdAt: Date
}

export const rawDataToBackupObject = (data: FractalResponseData): Backup => ({
    uuid: data.uuid,
    isSuccessful: data.is_successful,
    isLocked: data.is_locked,
    name: data.name,
    size: data.size,
    completedAt: data.completed_at ? new Date(data.completed_at) : undefined,
    createdAt: new Date(data.created_at),
})

export type BackupResponse = PaginatedResult<Backup> & { backupCount: number }

export default (
    uuid: string,
    { query, perPage = 10, ...params }: QueryParams
): Promise<BackupResponse> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/backups`, {
            params: {
                'filter[name]': query,
                'per_page': perPage,
                ...params,
            },
        })
            .then(({ data }) =>
                resolve({
                    items: (data.data || []).map((datum: any) =>
                        rawDataToBackupObject(datum)
                    ),
                    pagination: getPaginationSet(data.meta.pagination),
                    backupCount: data.meta.backup_count,
                })
            )
            .catch(reject)
    })
}