import useServersSWR from '@/api/admin/servers/useServersSWR'
import DescriptiveItemComponent from '@/components/elements/DescriptiveItemComponent'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import { NodeContext } from '@/state/admin/node'
import { debounce } from 'debounce'
import { useField } from 'formik'
import { useCallback, useEffect, useMemo, useState } from 'react'

const ServersSelectFormik = () => {
    const [{ value }] = useField('serverId')
    const [query, setQuery] = useState(value as string)

    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)

    const { data, mutate, isLoading, isValidating } = useServersSWR({ nodeId, query })
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
        setQuery(value as string)
        search()
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
            label='Assigned Server'
            data={servers}
            searchable
            searchValue={query}
            onSearchChange={handleOnSearch}
            itemComponent={DescriptiveItemComponent}
            nothingFound='No servers found'
            loading={isLoading || isValidating}
            clearable
            name='serverId'
        />
    )
}

export default ServersSelectFormik
