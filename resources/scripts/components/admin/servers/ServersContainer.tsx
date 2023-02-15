import ServersTable from '@/components/admin/servers/ServersTable'
import Button from '@/components/elements/Button'
import PageContentBlock from '@/components/elements/PageContentBlock'
import { useState } from 'react'
import CreateServerModal from '@/components/admin/servers/CreateServerModal'
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/react/20/solid'
import TextInput from '@/components/elements/inputs/TextInput'
import { useDebouncedValue } from '@mantine/hooks'
import SearchBar from '@/components/admin/SearchBar'

const ServersContainer = () => {
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const [open, setOpen] = useState(false)

    return (
        <div className='bg-background min-h-screen'>
            <CreateServerModal open={open} onClose={() => setOpen(false)} />
            <PageContentBlock title='Servers' showFlashKey='admin:servers'>
                <SearchBar value={query} onChange={e => setQuery(e.target.value)} buttonText='New Server' onClick={() => setOpen(true)} />
                <ServersTable query={debouncedQuery} />
            </PageContentBlock>
        </div>
    )
}

export default ServersContainer
