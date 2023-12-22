import { useDebouncedValue } from '@mantine/hooks'
import { useState } from 'react'
import { useFormContext } from 'react-hook-form'
import { useTranslation } from 'react-i18next'

import useServersSWR from '@/api/admin/servers/useServersSWR'

import SelectForm from '@/components/elements/forms/SelectForm'


interface Props {
    addressPoolId?: number | null
}

const ServersSelectForm = ({ addressPoolId }: Props) => {
    const { t: tSIndex } = useTranslation('admin.servers.index')
    const { t: tAIndex } = useTranslation('admin.addressPools.addresses')
    const { watch } = useFormContext()
    const serverId: string = watch('serverId')
    const [query, setQuery] = useState(serverId)
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const { data, isLoading, isValidating } = useServersSWR({
        addressPoolId,
        query: debouncedQuery,
        perPage: 10,
    })
    const servers =
        data?.items.map(server => ({
            value: server.internalId.toString(),
            label: server.name,
            description: server.hostname,
        })) ?? []

    return (
        <SelectForm
            label={tAIndex('assigned_server')}
            name={'serverId'}
            data={servers}
            searchable
            searchValue={query}
            onSearchChange={val => setQuery(val)}
            loading={isValidating || isLoading}
            nothingFound={tSIndex('no_server_found')}
            clearable
        />
    )
}

export default ServersSelectForm