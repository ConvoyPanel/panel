import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'
import useNodesSWR from '@/api/admin/nodes/useNodesSWR'
import { debounce } from 'debounce'
import SelectFormik from '@/components/elements/forms/SelectFormik'

interface Props {
    disabled?: boolean
}

const NodesSelectFormik = ({ disabled }: Props) => {
    const [{ value }] = useField('nodeId')
    const [query, setQuery] = useState(value as string)
    const { data, mutate, isValidating, isLoading } = useNodesSWR({ query })
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
        handleOnSearch(value as string)
    }, [value])

    const search = useCallback(
        debounce(() => {
            mutate()
        }, 500),
        []
    )

    const handleOnSearch = (query: string) => {
        setQuery(query)
        search()
    }

    return (
        <SelectFormik
            label={'Node'}
            data={nodes}
            searchable
            searchValue={query}
            onSearchChange={handleOnSearch}
            loading={isValidating || isLoading}
            nothingFound={'No nodes found'}
            name={'nodeId'}
            disabled={disabled}
        />
    )
}

export default NodesSelectFormik
