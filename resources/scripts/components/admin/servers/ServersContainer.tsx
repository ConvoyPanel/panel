import ServersTable from '@/components/admin/servers/ServersTable'
import Button from '@/components/elements/Button'
import PageContentBlock from '@/components/elements/PageContentBlock'
import { useState } from 'react'

const ServersContainer = () => {
    const [open, setOpen] = useState(false)

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title='Servers' showFlashKey='admin:servers'>
                <div className='flex justify-end items-center mb-3'>
                    <Button onClick={() => setOpen(true)} variant='filled'>
                        New Server
                    </Button>
                </div>
                <ServersTable />
            </PageContentBlock>
        </div>
    )
}

export default ServersContainer
