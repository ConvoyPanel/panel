import queryRemoteFile, { FileMetadata } from '@/api/admin/tools/queryRemoteFile'
import Button, { ButtonProps } from '@/components/elements/Button'
import { NodeContext } from '@/state/admin/node'
import { useState } from 'react'

interface Props extends ButtonProps {
    onQuery: (payload: FileMetadata) => void
    onFail: () => void
    link: string
}

const QueryFileButton = ({ link, onQuery, onFail, ...props }: Props) => {
    const [loading, setLoading] = useState(false)
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)

    const handleQuery = () => {
        setLoading(true)
        queryRemoteFile(nodeId, link)
            .then(metadata => {
                onQuery(metadata)
                setLoading(false)
            })
            .catch(() => {
                onFail()
                setLoading(false)
            })
    }

    return (
        <Button variant='filled' loading={loading} onClick={handleQuery} {...props}>
            Query
        </Button>
    )
}

export default QueryFileButton
