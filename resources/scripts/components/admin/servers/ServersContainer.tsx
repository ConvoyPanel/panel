import ServersTable from '@/components/admin/servers/ServersTable'
import PageContentBlock from '@/components/elements/PageContentBlock'
import { useState } from 'react'
import CreateServerModal from '@/components/admin/servers/CreateServerModal'
import { useDebouncedValue } from '@mantine/hooks'
import SearchBar from '@/components/admin/SearchBar'
import { useTranslation } from 'react-i18next'

const ServersContainer = () => {
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const [open, setOpen] = useState(false)
    const { t } = useTranslation('admin.servers.index')
    const { t: tStrings } = useTranslation('strings')

    return (
        <div className='bg-background min-h-screen'>
            <CreateServerModal open={open} onClose={() => setOpen(false)} />
            <PageContentBlock title={tStrings('server_other') ?? 'Servers'}>
                <SearchBar
                    value={query}
                    onChange={e => setQuery(e.target.value)}
                    buttonText={t('create_server')}
                    onClick={() => setOpen(true)}
                />
                <ServersTable query={debouncedQuery} />
            </PageContentBlock>
        </div>
    )
}

export default ServersContainer
