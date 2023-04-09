import useIsosSWR from '@/api/admin/nodes/isos/useIsosSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import CheckboxFormik from '@/components/elements/formik/CheckboxFormik'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'
import Modal from '@/components/elements/Modal'
import { NodeContext } from '@/state/admin/node'
import useFlash from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import { useEffect } from 'react'
import * as yup from 'yup'
import createIso, { ChecksumAlgorithm } from '@/api/admin/nodes/isos/createIso'
import { IsoResponse } from '@/api/admin/nodes/isos/getIsos'
import Button from '@/components/elements/Button'
import QueryFileButton from '@/components/admin/nodes/isoLibrary/QueryFileButton'
import { FileMetadata } from '@/api/admin/tools/queryRemoteFile'

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
            fileName: yup
                .string()
                .matches(/\.iso$/, 'File extension must end in .iso')
                .max(40, 'Limit up to 40 characters')
                .required('File name is required.'),
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
                    setSubmitting(false)
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

    const handleQuery = (meta: FileMetadata) => {
        form.setFieldValue('fileName', meta.fileName)
    }

    const handleQueryFail = () => {
        form.setFieldError('link', 'Invalid remote file')
    }

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
                        <div
                            className={`grid grid-cols-3 sm:grid-cols-4 gap-3 ${
                                form.touched.link && form.errors.link ? 'items-center' : 'items-end'
                            }`}
                        >
                            <TextInputFormik className='col-span-2 sm:col-span-3' name='link' label='Link' />
                            <QueryFileButton
                                onQuery={handleQuery}
                                onFail={handleQueryFail}
                                link={form.values.link}
                                disabled={Boolean(form.errors.link) || form.values.link.length === 0}
                                className={form.touched.link && form.errors.link ? '-mt-0.5' : ''}
                            />
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
