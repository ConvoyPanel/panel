import useServersSWR from '@/api/admin/servers/useServersSWR'
import DescriptiveItemComponent from '@/components/elements/DescriptiveItemComponent'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import { NodeContext } from '@/state/admin/node'
import { useDebouncedValue } from '@mantine/hooks'
import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'

const ServersSelectFormik = () => {
    const [{ value }] = useField('serverId')
    const [query, setQuery] = useState(value as string)

    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const [debouncedQuery] = useDebouncedValue(query, 200)

    const { data, isLoading, isValidating } = useServersSWR({ nodeId, query: debouncedQuery })
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
