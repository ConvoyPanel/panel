import UserContentBlock from '@/components/admin/users/UserContentBlock'
import ServersTable from '@/components/admin/servers/ServersTable'
import Button from '@/components/elements/Button'
import { useState } from 'react'
import CreateServerModal from '@/components/admin/servers/CreateServerModal'
import useUserSWR from '@/api/admin/users/useUserSWR'

const UserServersContainer = () => {
    const { data: user } = useUserSWR()
    const [open, setOpen] = useState(false)

    return (
        <div className='bg-background min-h-screen'>
            <CreateServerModal userId={user.id} open={open} onClose={() => setOpen(false)} />
            <UserContentBlock title={'Servers'}>
                <div className='flex justify-end items-center mb-3'>
                    <Button onClick={() => setOpen(true)} variant='filled'>
                        New Server
                    </Button>
                </div>
                <ServersTable userId={user.id} />
            </UserContentBlock>
        </div>
    )
}

export default UserServersContainer
