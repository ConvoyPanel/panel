import { useFlashKey } from '@/util/useFlash'
import usePagination from '@/util/usePagination'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'

import createUser from '@/api/admin/users/createUser'
import { UserResponse } from '@/api/admin/users/getUsers'
import useUsersSWR from '@/api/admin/users/useUsersSWR'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import MessageBox from '@/components/elements/MessageBox'
import Modal from '@/components/elements/Modal'
import CheckboxFormik from '@/components/elements/formik/CheckboxFormik'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'


interface Props {
    open: boolean
    onClose: () => void
}

const CreateUserButton = ({ open, onClose }: Props) => {
    const [page] = usePagination()
    const { clearFlashes, clearAndAddHttpError } =
        useFlashKey('admin:users.create')
    const { mutate } = useUsersSWR({ page })

    const form = useFormik({
        initialValues: {
            name: '',
            email: '',
            password: '',
            rootAdmin: false,
        },
        validationSchema: yup.object({
            name: yup
                .string()
                .max(191, 'Do not exceed 191 characters')
                .required('A name is required.'),
            email: yup
                .string()
                .email('Enter a valid email address')
                .required('An email address is required.'),
            password: yup
                .string()
                .matches(
                    /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/,
                    'Must Contain 8 Characters, One Uppercase, One Lowercase, One Number and One Special Case Character'
                )
                .required('A password is required.'),
            rootAdmin: yup.boolean(),
        }),
        onSubmit: async values => {
            clearFlashes()
            try {
                const user = await createUser(values)

                mutate(data => {
                    if (!data) return data

                    return {
                        ...data,
                        items: [user, ...data.items],
                    } as UserResponse
                }, false)

                handleClose()
            } catch (e) {
                clearAndAddHttpError(e as Error)
            }
        },
    })

    const handleClose = () => {
        clearFlashes()
        form.resetForm()
        onClose()
    }

    return (
        <>
            <Modal open={open} onClose={handleClose}>
                <Modal.Header>
                    <Modal.Title>Create a User</Modal.Title>
                </Modal.Header>

                <FormikProvider value={form}>
                    <form onSubmit={form.handleSubmit}>
                        <Modal.Body>
                            <FlashMessageRender
                                className='mb-5'
                                byKey={'admin:users.create'}
                            />
                            <TextInputFormik name={'name'} label={'Name'} />
                            <TextInputFormik name={'email'} label={'Email'} />
                            <TextInputFormik
                                name={'password'}
                                label={'Password'}
                                type={'password'}
                            />
                            <CheckboxFormik
                                name={'rootAdmin'}
                                label={'Administrator'}
                                className={'mt-3 relative'}
                            />
                            {form.values.rootAdmin && (
                                <MessageBox type={'warning'} title={'Warning'}>
                                    You are giving this user administrator
                                    privileges
                                </MessageBox>
                            )}
                        </Modal.Body>
                        <Modal.Actions>
                            <Modal.Action type='button' onClick={handleClose}>
                                Cancel
                            </Modal.Action>
                            <Modal.Action
                                type='submit'
                                loading={form.isSubmitting}
                            >
                                Create
                            </Modal.Action>
                        </Modal.Actions>
                    </form>
                </FormikProvider>
            </Modal>
        </>
    )
}

export default CreateUserButton