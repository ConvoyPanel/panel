import { useFlashKey } from '@/util/useFlash'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'

import deleteUser from '@/api/admin/users/deleteUser'
import useUserSWR from '@/api/admin/users/useUserSWR'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import MessageBox from '@/components/elements/MessageBox'


const DeleteUserContainer = () => {
    const { data: user } = useUserSWR()
    const [loading, setLoading] = useState(false)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        'admin:user:settings:delete'
    )
    const navigate = useNavigate()

    const handleDelete = async () => {
        clearFlashes()
        setLoading(true)
        try {
            await deleteUser(user!.id)

            navigate('/admin/users')
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
        setLoading(false)
    }

    return (
        <FormCard className='w-full border-error'>
            <FormCard.Body>
                <FormCard.Title>Delete User</FormCard.Title>
                <div className='space-y-3 mt-3'>
                    <FlashMessageRender byKey='admin:user:settings:delete' />

                    <p className='description-small !text-foreground'>
                        The user and its associated data will be deleted
                    </p>
                    {user.serversCount > 0 ? (
                        <MessageBox title='Error' type='error'>
                            This user cannot be deleted with servers still
                            associated with it.
                        </MessageBox>
                    ) : null}
                </div>
            </FormCard.Body>
            <FormCard.Footer>
                <Button
                    loading={loading}
                    disabled={user.serversCount > 0}
                    onClick={handleDelete}
                    variant='filled'
                    color='danger'
                    size='sm'
                >
                    Delete
                </Button>
            </FormCard.Footer>
        </FormCard>
    )
}

export default DeleteUserContainer