import { AdminUserContext } from '@/state/admin/user'
import { useFlashKey } from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'
import updateUser from '@/api/admin/users/updateUser'
import FormCard from '@/components/elements/FormCard'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import UsersSelectFormik from '@/components/admin/servers/UsersSelectFormik'
import NodesSelectFormik from '@/components/admin/servers/NodesSelectFormik'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import Button from '@/components/elements/Button'
import CheckboxFormik from '@/components/elements/forms/CheckboxFormik'

const UserInformationContainer = () => {
    const user = AdminUserContext.useStoreState(state => state.user.data!)
    const setUser = AdminUserContext.useStoreActions(actions => actions.user.setUser)
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:user:settings:general')

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            name: user.name,
            email: user.email,
            password: '',
            rootAdmin: user.rootAdmin,
        },
        validationSchema: yup.object({
            name: yup.string().max(191, 'Do not exceed 191 characters').required('A name is required.'),
            email: yup.string().email('Enter a valid email address').required('An email address is required.'),
            password: yup
                .string()
                .matches(
                    /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/,
                    'Must Contain 8 Characters, One Uppercase, One Lowercase, One Number and One Special Case Character'
                )
                .optional(),
            rootAdmin: yup.boolean(),
        }),
        onSubmit: async ({ password, ...values }) => {
            clearFlashes()
            try {
                const updatedUser = await updateUser(user.id, {
                    ...values,
                    password: password.length > 0 ? password : null,
                })

                setUser(updatedUser)
            } catch (e) {
                clearAndAddHttpError(e as Error)
            }
        },
    })

    return (
        <FormCard className='w-full'>
            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <FormCard.Body>
                        <FormCard.Title>User Information</FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender byKey='admin:user:settings:general' />
                            <TextInputFormik name='name' label='Name' />
                            <TextInputFormik name='email' label='Email' />
                            <TextInputFormik name='password' label='Password' placeholder={'Override password'} />
                            <CheckboxFormik name={'rootAdmin'} label={'Administrator'} className={'mt-3'} />
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button
                            loading={form.isSubmitting}
                            disabled={!form.dirty}
                            type='submit'
                            variant='filled'
                            color='success'
                            size='sm'
                        >
                            Save
                        </Button>
                    </FormCard.Footer>
                </form>
            </FormikProvider>
        </FormCard>
    )
}

export default UserInformationContainer
