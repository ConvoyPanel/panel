import useLocationsSWR from '@/api/admin/locations/useLocationsSWR'
import createNode from '@/api/admin/nodes/createNode'
import { NodeResponse } from '@/api/admin/nodes/getNodes'
import useNodesSWR from '@/api/admin/nodes/useNodesSWR'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import SelectFormik from '@/components/elements/forms/SelectFormik'
import TextInputFormik from '@/components/elements/forms/TextInputFormik'
import Select from '@/components/elements/inputs/Select'
import Modal from '@/components/elements/Modal'
import useFlash from '@/util/useFlash'
import { debounce } from 'debounce'
import { FormikProvider, useFormik } from 'formik'
import { useCallback, useMemo, useState } from 'react'
import * as yup from 'yup'

interface Props {
    open: boolean
    onClose: () => void
}

const CreateNodeModal = ({ open, onClose }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()

    const [loadingQuery, setLoadingQuery] = useState(false)
    const [query, setQuery] = useState('')
    const { mutate: mutateNodes } = useNodesSWR({ page: 1 })
    const { data, mutate } = useLocationsSWR({ query })

    const locations = useMemo(
        () =>
            data?.items.map(location => ({
                value: location.id.toString(),
                label: location.shortCode,
            })) ?? [],
        [data]
    )

    const search = useCallback(
        debounce(() => {
            setLoadingQuery(true)
            mutate().then(() => setLoadingQuery(false))
        }, 500),
        []
    )

    const handleOnSearch = (query: string) => {
        setQuery(query)
        search()
    }

    const form = useFormik({
        initialValues: {
            name: '',
            locationId: undefined as number | undefined,
            cluster: '',
            tokenId: '',
            secret: '',
            fqdn: '',
            port: 8006,
            memory: undefined as number | undefined,
            memoryOverallocate: 0,
            disk: undefined as number | undefined,
            diskOverallocate: 0,
            vmStorage: '',
            backupStorage: '',
            isoStorage: '',
            network: '',
        },
        validationSchema: yup.object({
            name: yup.string().required('Specify a name').max(191, 'Please limit up to 191 characters'),
            locationId: yup.number().required('Specify a location'),
            cluster: yup.string().required('Specify a cluster').max(191, 'Please limit up to 191 characters'),
            tokenId: yup.string().required('Specify a token ID').max(191, 'Please limit up to 191 characters'),
            secret: yup.string().required('Specify a secret').max(191, 'Please limit up to 191 characters'),
            fqdn: yup
                .string()
                .matches(
                    /((https?):\/\/)?(www.)?[a-z0-9]+(\.[a-z]{2,}){1,3}(#?\/?[a-zA-Z0-9#]+)*\/?(\?[a-zA-Z0-9-_]+=[a-zA-Z0-9-%]+&?)?$/,
                    'Enter a valid FQDN'
                )
                .required('Specify a FQDN')
                .max(191, 'Please limit up to 191 characters'),
            port: yup
                .number()
                .integer()
                .required('Specify a port')
                .min(1, 'Please specify a valid port')
                .max(65535, 'Please specify a valid port'),
            memory: yup.number().integer().required('Specify a memory').min(0, 'Please specify a valid memory'),
            memoryOverallocate: yup
                .number()
                .integer()
                .required('Specify a memory overallocate')
                .min(0, 'Please specify a valid memory overallocate'),
            disk: yup.number().integer().required('Specify a disk').min(0, 'Please specify a valid disk'),
            diskOverallocate: yup
                .number()
                .integer()
                .required('Specify a disk overallocate')
                .min(0, 'Please specify a valid disk overallocate'),
            vmStorage: yup.string().required('Specify a VM storage').max(191, 'Please limit up to 191 characters'),
            backupStorage: yup
                .string()
                .required('Specify a backup storage')
                .max(191, 'Please limit up to 191 characters'),
            isoStorage: yup.string().required('Specify a ISO storage').max(191, 'Please limit up to 191 characters'),
            network: yup.string().required('Specify a network').max(191, 'Please limit up to 191 characters'),
        }),
        onSubmit: ({ memory, disk, locationId, ...values }, { setSubmitting }) => {
            setSubmitting(true)
            clearFlashes('admin:nodes:create')
            createNode({
                locationId: locationId!,
                memory: memory! * 1048576,
                disk: disk! * 1048576,
                ...values,
            })
                .then(node => {
                    mutateNodes(
                        data =>
                            ({
                                ...data,
                                items: [node].concat(data!.items),
                            } as NodeResponse),
                        false
                    )
                    handleClose()
                })
                .catch(error => {
                    clearAndAddHttpError({ key: 'admin:nodes:create', error })
                    setSubmitting(false)
                })
        },
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>Create a Node</Modal.Title>
            </Modal.Header>

            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={'admin:nodes:create'} />
                        <TextInputFormik name='name' label='Display Name' />
                        <SelectFormik
                            label='Location Group'
                            placeholder='fuk u chit'
                            data={locations}
                            searchable
                            searchValue={query}
                            onSearchChange={handleOnSearch}
                            loading={data === undefined || loadingQuery}
                            nothingFound='No locations found'
                            name='locationId'
                        />
                        <TextInputFormik name='cluster' label='Cluster' />
                        <div className='grid gap-3 grid-cols-2'>
                            <TextInputFormik name='tokenId' label='Token ID' />
                            <TextInputFormik name='secret' label='Secret' />
                        </div>
                        <TextInputFormik name='fqdn' label='FQDN' />
                        <TextInputFormik name='port' label='Port' />
                        <div className='grid gap-3 grid-cols-2'>
                            <TextInputFormik name='memory' label='Memory (MiB)' />
                            <TextInputFormik name='memoryOverallocate' label='Memory Overallocate (%)' />
                        </div>
                        <div className='grid gap-3 grid-cols-2'>
                            <TextInputFormik name='disk' label='Disk (MiB)' />
                            <TextInputFormik name='diskOverallocate' label='Disk Overallocate (%)' />
                        </div>
                        <div className='grid gap-3 grid-cols-3'>
                            <TextInputFormik name='vmStorage' label='VM Storage' placeholder='local' />
                            <TextInputFormik name='backupStorage' label='Backup Storage' placeholder='local' />
                            <TextInputFormik name='isoStorage' label='ISO Storage' placeholder='local' />
                        </div>
                            <TextInputFormik name='network' label='Network' placeholder='vmbr0' />
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

export default CreateNodeModal
