import { ISO, rawDataToISO } from '@/api/admin/nodes/isos/getIsos'
import http from '@/api/http'

export type ChecksumAlgorithm =
    | 'md5'
    | 'sha1'
    | 'sha224'
    | 'sha256'
    | 'sha384'
    | 'sha512'

interface CreateIsoParameters {
    shouldDownload: boolean
    name: string
    fileName: string
    hidden: boolean
    link?: string
    checksumAlgorithm?: ChecksumAlgorithm
    checksum?: string
}

const createIso = async (
    nodeId: number,
    {
        shouldDownload,
        fileName,
        checksumAlgorithm,
        checksum,
        ...data
    }: CreateIsoParameters
): Promise<ISO> => {
    const {
        data: { data: responseData },
    } = await http.post(`/api/admin/nodes/${nodeId}/isos`, {
        should_download: shouldDownload,
        file_name: fileName,
        checksum_algorithm: checksumAlgorithm,
        checksum: checksumAlgorithm ? checksum : undefined,
        ...data,
    })

    return rawDataToISO(responseData)
}

export default createIso