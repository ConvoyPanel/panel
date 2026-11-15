import { useFlashKey } from '@/util/useFlash'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'

import deleteUser from '@/api/admin/users/deleteUser'
import useUserSWR from '@/api/admin/users/useUserSWR'

import Button from '@/components/elements/Button'
import ConfirmationModal from '@/components/elements/ConfirmationModal'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import MessageBox from '@/components/elements/MessageBox'

const DeleteUserContainer = () => {
    const { data: user } = useUserSWR()
    const [confirming, setConfirming] = useState(false)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        'admin:user:settings:delete'
    )
    const navigate = useNavigate()

    const handleDelete = async () => {
        clearFlashes()
        try {
            await deleteUser(user!.id)

            navigate('/admin/users')
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    return (
        <FormCard className='w-full border-error'>
            <ConfirmationModal
                open={confirming}
                title={`Delete ${user.name}?`}
                description="Deleting this user also deletes their SSH keys and API tokens. This can't be undone."
                confirmLabel='Delete'
                onConfirm={handleDelete}
                onClose={() => setConfirming(false)}
            />
            <FormCard.Body>
                <FormCard.Title>Delete User</FormCard.Title>
                <div className='space-y-3 mt-3'>
                    <FlashMessageRender byKey='admin:user:settings:delete' />
                    {user.serversCount > 0 ? (
                        <MessageBox title='Error' type='error'>
                            Delete or reassign this user's servers first.
                        </MessageBox>
                    ) : null}
                </div>
            </FormCard.Body>
            <FormCard.Footer>
                <Button
                    disabled={user.serversCount > 0}
                    onClick={() => setConfirming(true)}
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
