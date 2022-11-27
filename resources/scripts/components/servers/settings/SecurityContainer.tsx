import getSecurity from '@/api/server/settings/getSecurity'
import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import TextInput from '@/components/elements/inputs/TextInput'
import { ServerContext } from '@/state/server'
import useFlash from '@/util/useFlash'
import useNotify from '@/util/useNotify'
import { useFormik } from 'formik'
import useSWR from 'swr'
import * as yup from 'yup'
import Textarea from '@/components/elements/inputs/Textarea'
import { SegmentedControl } from '@mantine/core'
import updateSecurity from '@/api/server/settings/updateSecurity'

const SecurityContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid)
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const notify = useNotify()

    const { data, mutate } = useSWR(['server:settings:security', uuid], () => getSecurity(uuid))

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            type: 'cipassword',
            sshKeys: data?.sshKeys ?? '',
            password: '',
        },
        validationSchema: yup.object({
            type: yup.string().required(),
            password: yup
                .string()
                .when('type', {
                    is: 'cipassword',
                    then: yup.string().required('Must enter a password'),
                })
                .matches(
                    /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/,
                    'Must Contain 8 Characters, One Uppercase, One Lowercase, One Number and One Special Case Character'
                ),
                sshKeys: yup.string(),
        }),
        onSubmit: ({ type, password, sshKeys }, { setSubmitting }) => {
            clearFlashes('server:settings:auth')

            updateSecurity(uuid, {
                // @ts-expect-error
                type,
                password: type === 'cipassword' ? password : undefined,
                sshKeys: type === 'sshkeys' ? sshKeys : undefined,
            })
                .then(() => {
                    notify({
                        title: 'Updated',
                        message: 'Updated security settings',
                        color: 'green',
                    })
                    mutate(() => ({ sshKeys }), false)

                    setSubmitting(false)
                })
                .catch(error => {
                    clearAndAddHttpError({ key: 'server:settings:auth', error })
                    setSubmitting(false)
                })
        },
    })

    return (
        <FormSection title='Security'>
            <FormCard className='w-full'>
                <form onSubmit={form.handleSubmit}>
                    <FormCard.Body>
                        <FormCard.Title>Authentication</FormCard.Title>
                        <div className='space-y-3 mt-3'>
                            <FlashMessageRender byKey='server:settings:auth' />
                            <SegmentedControl
                                disabled={form.isSubmitting}
                                value={form.values.type}
                                onChange={val => form.setFieldValue('type', val)}
                                data={[
                                    { value: 'cipassword', label: 'Password' },
                                    { value: 'sshkeys', label: 'SSH Key' },
                                ]}
                            />
                            {form.values.type === 'cipassword' && (
                                <TextInput
                                    value={form.values.password}
                                    onChange={form.handleChange}
                                    error={form.touched.password ? form.errors.password : undefined}
                                    disabled={form.isSubmitting}
                                    name='password'
                                    label='Password'
                                />
                            )}
                            {form.values.type === 'sshkeys' && (
                                <Textarea
                                    value={form.values.sshKeys}
                                    onChange={form.handleChange}
                                    error={form.touched.sshKeys ? form.errors.sshKeys : undefined}
                                    disabled={form.isSubmitting}
                                    name='sshKeys'
                                    label='Contents'
                                />
                            )}
                        </div>
                    </FormCard.Body>
                    <FormCard.Footer>
                        <Button loading={form.isSubmitting} type='submit' variant='filled' color='success' size='sm'>
                            Save
                        </Button>
                    </FormCard.Footer>
                </form>
            </FormCard>
        </FormSection>
    )
}

export default SecurityContainer
