import http from '@/api/http'

export interface FileMetadata {
    fileName: string
    size: number
    mimeType: string
}

const rawDataToFileMetadata = (rawData: any): FileMetadata => ({
    fileName: rawData.file_name,
    size: rawData.size,
    mimeType: rawData.mime_type,
})

const queryRemoteFile = async (
    nodeId: number,
    link: string
): Promise<FileMetadata> => {
    const {
        data: { data },
    } = await http.get(`/api/admin/nodes/${nodeId}/tools/query-remote-file`, {
        params: {
            link,
        },
    })

    return rawDataToFileMetadata(data)
}

export default queryRemoteFile