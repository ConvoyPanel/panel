import { useDebouncedValue } from '@mantine/hooks'
import { useField } from 'formik'
import { useEffect, useMemo, useState } from 'react'

import useNodeSWR from '@/api/admin/nodes/useNodeSWR'
import useServersSWR from '@/api/admin/servers/useServersSWR'

import DescriptiveItemComponent from '@/components/elements/DescriptiveItemComponent'
import SelectFormik from '@/components/elements/formik/SelectFormik'


const ServersSelectFormik = () => {
    const [{ value }] = useField('serverId')
    const [query, setQuery] = useState(value as string)

    const { data: node } = useNodeSWR()
    const [debouncedQuery] = useDebouncedValue(query, 200)

    const { data, isLoading, isValidating } = useServersSWR({
        nodeId: node.id,
        query: debouncedQuery,
    })
    const servers = useMemo(
        () =>
            data?.items.map(server => ({
                value: server.internalId.toString(),
                label: server.name,
                description: server.hostname,
            })) ?? [],
        [data]
    )

    useEffect(() => {
        setQuery((value as string | null) ?? '')
    }, [value])

    return (
        <SelectFormik
            label='Assigned Server'
            data={servers}
            searchable
            searchValue={query}
            onSearchChange={val => setQuery(val)}
            itemComponent={DescriptiveItemComponent}
            nothingFound='No servers found'
            loading={isLoading || isValidating}
            clearable
            name='serverId'
        />
    )
}

export default ServersSelectFormik