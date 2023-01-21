import { FormikProvider } from 'formik'
import FormCard from '@/components/elements/FormCard'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import MessageBox from '@/components/elements/MessageBox'
import CheckboxFormik from '@/components/elements/forms/CheckboxFormik'
import Button from '@/components/elements/Button'
import { AdminUserContext } from '@/state/admin/user'
import { useState } from 'react'
import { useFlashKey } from '@/util/useFlash'
import deleteUser from '@/api/admin/users/deleteUser'
import { useNavigate } from 'react-router-dom'

const DeleteUserContainer = () => {
    const user = AdminUserContext.useStoreState(state => state.user.data!)
    const [loading, setLoading] = useState(false)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:user:settings:delete')
    const navigate = useNavigate()

    const handleDelete = async () => {
        clearFlashes()
        setLoading(true)
        try {
            await deleteUser(user.id)

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
                            This user cannot be deleted with servers still associated with it.
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
