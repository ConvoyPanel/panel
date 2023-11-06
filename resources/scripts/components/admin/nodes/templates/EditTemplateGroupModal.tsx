import useFlash from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'

import createTemplateGroup from '@/api/admin/nodes/templateGroups/createTemplateGroup'
import { TemplateGroup } from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import updateTemplateGroup from '@/api/admin/nodes/templateGroups/updateTemplateGroup'
import useTemplateGroupsSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import CheckboxFormik from '@/components/elements/formik/CheckboxFormik'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'


interface Props {
    open: boolean
    onClose: () => void
    group?: TemplateGroup
}

const EditTemplateGroupModal = ({ open, onClose, group }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const { data: node } = useNodeSWR()
    const { mutate } = useTemplateGroupsSWR(node.id)

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            name: group?.name ?? '',
            hidden: group?.hidden ?? false,
        },
        validationSchema: yup.object({
            name: yup
                .string()
                .required('Specify a name')
                .max(40, 'Please limit up to 40 characters'),
            hidden: yup.boolean(),
        }),
        onSubmit: (values, { setSubmitting }) => {
            setSubmitting(true)
            clearFlashes('admin:node:template-groups.edit')

            if (group) {
                updateTemplateGroup(node.id, group.uuid, values)
                    .then(({ name, hidden }) => {
                        handleClose()

                        // @ts-expect-error - groups should be defined. Though, it might not when there's a network error
                        mutate(
                            groups =>
                                groups.map(g =>
                                    g.uuid === group.uuid
                                        ? { ...g, name, hidden }
                                        : g
                                ),
                            false
                        )
                    })
                    .catch(error => {
                        clearAndAddHttpError({
                            key: 'admin:node:template-groups.edit',
                            error,
                        })
                        setSubmitting(false)
                    })
            } else {
                createTemplateGroup(node.id, values)
                    .then(newGroup => {
                        handleClose()

                        mutate(groups => {
                            if (!groups) return groups

                            return [
                                ...groups,
                                {
                                    ...newGroup,
                                    templates: [],
                                },
                            ]
                        }, false)
                    })
                    .catch(error => {
                        clearAndAddHttpError({
                            key: 'admin:node:template-groups.edit',
                            error,
                        })
                        setSubmitting(false)
                    })
            }
        },
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>
                    {group ? 'Edit' : 'New'} Template Group
                </Modal.Title>
            </Modal.Header>

            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <Modal.Body>
                        <FlashMessageRender
                            className='mb-5'
                            byKey={'admin:node:template-groups.edit'}
                        />
                        <TextInputFormik name='name' label='Display Name' />
                        <CheckboxFormik
                            className='mt-3'
                            name='hidden'
                            label='Hidden'
                        />
                    </Modal.Body>
                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            Cancel
                        </Modal.Action>
                        <Modal.Action type='submit' loading={form.isSubmitting}>
                            {group ? 'Update' : 'Create'}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormikProvider>
        </Modal>
    )
}

export default EditTemplateGroupModal