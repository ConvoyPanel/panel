import useIsosSWR from '@/api/admin/nodes/isos/useIsosSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import CheckboxFormik from '@/components/elements/forms/CheckboxFormik'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import Modal from '@/components/elements/Modal'
import { NodeContext } from '@/state/admin/node'
import useFlash from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import { useEffect } from 'react'
import * as yup from 'yup'
import createIso, { ChecksumAlgorithm } from '@/api/admin/nodes/isos/createIso'
import { IsoResponse } from '@/api/admin/nodes/isos/getIsos'
import Button from '@/components/elements/Button'

interface Props {
    open: boolean
    onClose: () => void
}

const CreateIsoModal = ({ open, onClose }: Props) => {
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const { mutate } = useIsosSWR({ nodeId })
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    const form = useFormik({
        initialValues: {
            name: '',
            link: '',
            fileName: '',
            checksumAlgorithm: 'none',
            checksum: undefined,
            hidden: false,
        },
        validationSchema: yup.object().shape({
            name: yup.string().required('Name is required.'),
            link: yup.string().url('Invalid link').required('Link is required.'),
            fileName: yup.string().max(40, 'Limit up to 40 characters').required('File name is required.'),
            hidden: yup.boolean(),
            checksumAlgorithm: yup
                .string()
                .oneOf(['none', 'md5', 'sha1', 'sha224', 'sha256', 'sha384', 'sha512'])
                .optional(),
            checksum: yup.string().when('checksumAlgorithm', {
                is: (value: string | undefined) => value !== 'none',
                then: yup.string().required('Specify the checksum'),
            }),
        }),
        onSubmit: ({ checksumAlgorithm, ...values }, { setSubmitting }) => {
            setSubmitting(true)
            clearFlashes('admin:node:isos.create')

            createIso(nodeId, {
                checksumAlgorithm: checksumAlgorithm === 'none' ? undefined : (checksumAlgorithm as ChecksumAlgorithm),
                ...values,
            })
                .then(iso => {
                    mutate(
                        data =>
                            ({
                                ...data,
                                items: [iso, ...data!.items],
                            } as typeof data),
                        false
                    )
                    handleClose()
                })
                .catch(error => {
                    clearAndAddHttpError({ key: 'admin:node:isos.create', error })
                    setSubmitting(false)
                })
        },
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    const checksumAlgorithms = [
        { value: 'none', label: 'None' },
        { value: 'md5', label: 'MD5' },
        { value: 'sha1', label: 'SHA1' },
        { value: 'sha224', label: 'SHA224' },
        { value: 'sha256', label: 'SHA256' },
        { value: 'sha384', label: 'SHA384' },
        { value: 'sha512', label: 'SHA512' },
    ]

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>Create an ISO</Modal.Title>
            </Modal.Header>

            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={'admin:node:isos.create'} />

                        <TextInputFormik name='name' label='Display Name' />
                        <div className={`grid grid-cols-4 gap-3 ${form.errors.link ? 'items-center' : 'items-end'}`}>
                            <TextInputFormik className='col-span-3' name='link' label='Link' />
                            <Button disabled={!form.touched.link || Boolean(form.errors.link)} className={form.errors.link ? '-mt-0.5' : ''} variant='filled'>Query</Button>
                        </div>
                        <TextInputFormik name='fileName' label='File Name' />
                        <SelectFormik data={checksumAlgorithms} name='checksumAlgorithm' label='Checksum Algorithm' />
                        <TextInputFormik
                            disabled={form.values.checksumAlgorithm === 'none'}
                            name='checksum'
                            label='Checksum'
                        />
                        <CheckboxFormik className='mt-3' name='hidden' label='Hidden' />
                    </Modal.Body>

                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            Cancel
                        </Modal.Action>
                        <Modal.Action type='submit' loading={form.isSubmitting}>
                            Create
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormikProvider>
        </Modal>
    )
}

export default CreateIsoModal
