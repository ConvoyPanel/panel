import { useField } from 'formik'
import { useEffect, useMemo, useState } from 'react'
import useNodesSWR from '@/api/admin/nodes/useNodesSWR'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import { useDebouncedValue } from '@mantine/hooks'

interface Props {
    disabled?: boolean
}

const NodesSelectFormik = ({ disabled }: Props) => {
    const [{ value }] = useField('nodeId')
    const [query, setQuery] = useState(value as string)

    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, isValidating, isLoading } = useNodesSWR({ query: debouncedQuery })
    const nodes = useMemo(
        () =>
            data?.items.map(node => ({
                value: node.id.toString(),
                label: node.name,
                description: node.fqdn,
            })) ?? [],
        [data]
    )

    useEffect(() => {
        setQuery(value as string)
    }, [value])

    return (
        <SelectFormik
            label={'Node'}
            data={nodes}
            searchable
            searchValue={query}
            onSearchChange={query => setQuery(query)}
            loading={isValidating || isLoading}
            nothingFound={'No nodes found'}
            name={'nodeId'}
            disabled={disabled}
        />
    )
}

export default NodesSelectFormik
