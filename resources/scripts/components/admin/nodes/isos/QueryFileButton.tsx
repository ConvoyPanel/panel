import queryRemoteFile from '@/api/admin/tools/queryRemoteFile'
import Button from '@/components/elements/Button'
import { NodeContext } from '@/state/admin/node'
import { useState } from 'react'
import { useFormContext } from 'react-hook-form'
import { useTranslation } from 'react-i18next'

const QueryFileButton = () => {
    const { getValues, setError, setValue } = useFormContext()
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const [loading, setLoading] = useState(false)
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.nodes.isos')

    const query = async () => {
        setLoading(true)

        try {
            const metadata = await queryRemoteFile(nodeId, getValues('link'))

            setValue('fileName', metadata.fileName)
        } catch {
            setError('link', {
                message: t('create_modal.fail_to_query_remote_file_error') ?? 'Failed to query remote file.',
            })
        }

        setLoading(false)
    }

    return (
        <Button variant='filled' loading={loading} onClick={query} className='mt-[1.625rem]'>
            {tStrings('query')}
        </Button>
    )
}

export default QueryFileButton
