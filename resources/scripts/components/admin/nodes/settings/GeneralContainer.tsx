import updateNode from '@/api/admin/nodes/updateNode'
import LocationsSelectFormik from '@/components/admin/nodes/LocationsSelectFormik'
import DeleteNodeContainer from '@/components/admin/nodes/settings/DeleteNodeContainer'
import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'
import FormSection from '@/components/elements/FormSection'
import { NodeContext } from '@/state/admin/node'
import useFlash, { useFlashKey } from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'

const GeneralContainer = () => {
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:node:settings:general')

    const node = NodeContext.useStoreState(state => state.node.data!)
    const setNode = NodeContext.useStoreActions(actions => actions.node.setNode)

    const form = useFormik({
        initialValues: {
            name: node.name,
            locationId: node.locationId.toString(),
            cluster: node.cluster,
            tokenId: undefined as string | undefined,
            secret: undefined as string | undefined,
            fqdn: node.fqdn,
            port: node.port,
            memory: node.memory / 1048576,
            memoryOverallocate: node.memoryOverallocate,
            disk: node.disk / 1048576,
            diskOverallocate: node.diskOverallocate,
            vmStorage: node.vmStorage,
            backupStorage: node.backupStorage,
            isoStorage: node.isoStorage,
            network: node.network,
        },
        validationSchema: yup.object({
            name: yup.string().required('Specify a name').max(191, 'Please limit up to 191 characters'),
            locationId: yup.number().required('Specify a location'),
            cluster: yup.string().required('Specify a cluster').max(191, 'Please limit up to 191 characters'),
            tokenId: yup.string().max(191, 'Please limit up to 191 characters').optional(),
            secret: yup.string().max(191, 'Please limit up to 191 characters').optional(),
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
        onSubmit: async ({ locationId, memory, disk, ...values }, { setSubmitting }) => {
            clearFlashes()
            setSubmitting(true)
            try {
                const updatedNode = await updateNode(node.id, {
                    ...values,
                    memory: memory * 1048576,
                    disk: disk * 1048576,
                    locationId: parseInt(locationId),
                })

                setNode(updatedNode)
            } catch (error) {
                clearAndAddHttpError(error as Error)
            }

            setSubmitting(false)
        },
    })
    return (
        <>
            <FormCard className='w-full'>
                <FormikProvider value={form}>
                    <form onSubmit={form.handleSubmit}>
                        <FormCard.Body>
                            <FormCard.Title>Node Information</FormCard.Title>
                            <div className='space-y-3 mt-3'>
                                <FlashMessageRender byKey='admin:node:settings:general' />
                                <TextInputFormik name='name' label='Display Name' />
                                <LocationsSelectFormik />
                                <TextInputFormik name='cluster' label='Node Name In Proxmox' />
                                <TextInputFormik name='fqdn' label='FQDN' />
                                <TextInputFormik name='port' label='Port' />
                                <div className='grid gap-3 grid-cols-2'>
                                    <TextInputFormik
                                        name='tokenId'
                                        label='Token ID'
                                        placeholder='Override credentials'
                                    />
                                    <TextInputFormik name='secret' label='Secret' placeholder='Override credentials' />
                                </div>
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
            <DeleteNodeContainer />
        </>
    )
}

export default GeneralContainer
