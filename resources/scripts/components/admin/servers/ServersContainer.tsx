import ServersTable from '@/components/admin/servers/ServersTable'
import Button from '@/components/elements/Button'
import PageContentBlock from '@/components/elements/PageContentBlock'
import { useState } from 'react'
import CreateServerModal from '@/components/admin/servers/CreateServerModal'
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/react/20/solid'
import TextInput from '@/components/elements/inputs/TextInput'
import { useDebouncedValue } from '@mantine/hooks'

const ServersContainer = () => {
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const [open, setOpen] = useState(false)

    return (
        <div className='bg-background min-h-screen'>
            <CreateServerModal open={open} onClose={() => setOpen(false)} />
            <PageContentBlock title='Servers' showFlashKey='admin:servers'>
                <div className='flex space-x-2 items-center mb-3'>
                    <TextInput
                        icon={<MagnifyingGlassIcon className='text-accent-400 w-4 h-4' />}
                        className='grow'
                        value={query}
                        onChange={e => setQuery(e.target.value)}
                        placeholder='Search...'
                    />
                     <Button
                        className='grid sm:hidden place-items-center'
                        onClick={() => setOpen(true)}
                        shape='square'
                        variant='filled'
                    >
                        <PlusIcon className='w-5 h-5 block sm:hidden' />
                    </Button>
                    <Button className='hidden sm:block' onClick={() => setOpen(true)} variant='filled'>
                        New Server
                    </Button>
                </div>
                <ServersTable query={debouncedQuery} />
            </PageContentBlock>
        </div>
    )
}

export default ServersContainer
