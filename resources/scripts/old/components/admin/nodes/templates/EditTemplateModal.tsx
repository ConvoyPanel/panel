import useFlash from '@/util/useFlash'
import { FormikProvider, useFormik } from 'formik'
import * as yup from 'yup'

import {
    Template,
    TemplateGroup,
} from '@/api/admin/nodes/templateGroups/getTemplateGroups'
import createTemplate from '@/api/admin/nodes/templateGroups/templates/createTemplate'
import updateTemplate from '@/api/admin/nodes/templateGroups/templates/updateTemplate'
import useTemplateGroupsSWR from '@/api/admin/nodes/templateGroups/useTemplateGroupsSWR'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import CheckboxFormik from '@/components/elements/formik/CheckboxFormik'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'


interface Props {
    open: boolean
    onClose: () => void
    template?: Template
    group: TemplateGroup
}

const EditTemplateModal = ({ open, onClose, template, group }: Props) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash()
    const { data: node } = useNodeSWR()
    const { mutate } = useTemplateGroupsSWR(node.id)

    const form = useFormik({
        enableReinitialize: true,
        initialValues: {
            name: template?.name ?? '',
            vmid: template?.vmid ?? ('' as unknown as number | undefined),
            hidden: template?.hidden ?? false,
        },
        validationSchema: yup.object({
            name: yup
                .string()
                .required('Specify a name')
                .max(40, 'Please limit up to 40 characters'),
            vmid: yup
                .number()
                .required('Specify a VMID')
                .min(100, 'VMID must be greater than 100')
                .max(999999999, 'VMID must be less than 999999999'),
            hidden: yup.boolean(),
        }),
        onSubmit: async ({ vmid, ...values }, { setSubmitting }) => {
            setSubmitting(true)
            clearFlashes('admin:node:template-group:templates.edit')

            try {
                if (template) {
                    await updateTemplate(node.id, group.uuid, template.uuid, {
                        ...values,
                        vmid: vmid as number,
                    })

                    handleClose()

                    // replace the details of the template in the corresponding group
                    mutate(
                        groups =>
                            groups!.map(oldGroup => {
                                if (oldGroup.uuid === group.uuid) {
                                    return {
                                        ...oldGroup,
                                        templates: oldGroup.templates!.map(
                                            t => {
                                                if (t.uuid === template.uuid) {
                                                    return {
                                                        ...t,
                                                        ...values,
                                                        vmid: vmid as number,
                                                    }
                                                }

                                                return t
                                            }
                                        ),
                                    }
                                }

                                return oldGroup
                            }),
                        false
                    )
                } else {
                    const template = await createTemplate(node.id, group.uuid, {
                        ...values,
                        vmid: vmid as number,
                    })
                    handleClose()

                    // add the new template to the corresponding group
                    mutate(
                        groups =>
                            groups!.map(oldGroup => {
                                if (oldGroup.uuid === group.uuid) {
                                    return {
                                        ...oldGroup,
                                        templates: [
                                            ...(oldGroup.templates ?? []),
                                            template,
                                        ],
                                    }
                                }

                                return oldGroup
                            }),
                        false
                    )
                }
            } catch (error) {
                clearAndAddHttpError({
                    key: 'admin:node:template-group:templates.edit',
                    error,
                })
            }

            setSubmitting(false)
        },
    })

    const handleClose = () => {
        form.resetForm()
        onClose()
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{template ? 'Edit' : 'New'} Template</Modal.Title>
            </Modal.Header>

            <FormikProvider value={form}>
                <form onSubmit={form.handleSubmit}>
                    <Modal.Body>
                        <FlashMessageRender
                            className='mb-5'
                            byKey={'admin:node:template-group:templates.edit'}
                        />
                        <TextInputFormik name='name' label='Display Name' />
                        <TextInputFormik name='vmid' label='VMID' />
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
                            {template ? 'Update' : 'Create'}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormikProvider>
        </Modal>
    )
}

export default EditTemplateModal