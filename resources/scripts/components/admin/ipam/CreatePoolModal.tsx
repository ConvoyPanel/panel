import { KeyedMutator } from 'swr'
import { useTranslation } from 'react-i18next'
import { useFlashKey } from '@/util/useFlash'
import { z } from 'zod'
import { FormProvider, useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import Modal from '@/components/elements/Modal'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import { AddressPoolResponse } from '@/api/admin/addressPools/getAddressPools'
import NodesMultiSelectForm from '@/components/admin/ipam/NodesMultiSelectForm'

interface Props {
    open: boolean
    onClose: () => void
    mutate: KeyedMutator<AddressPoolResponse>
}

const CreatePoolModal = ({ open, onClose, mutate }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const { t } = useTranslation('admin.addressPools.index')
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(`admin.addressPools.create`)

    const schema = z.object({
        name: z.string().nonempty().max(191),
        nodeIds: z.array(z.coerce.number()),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            nodeIds: [],
        },
    })

    const handleClose = () => {
        form.reset()
        onClose()
    }

    const submit = async (_data: any) => {
        const data = _data as z.infer<typeof schema>

        clearFlashes()
        try {
            handleClose()
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    return (
        <Modal open={open} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{t('create_pool')}</Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={`admin.addressPools.create`} />
                        <TextInputForm name='name' label={tStrings('name')} />
                        <NodesMultiSelectForm />
                    </Modal.Body>

                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            {tStrings('cancel')}
                        </Modal.Action>
                        <Modal.Action type='submit' loading={form.formState.isSubmitting}>
                            {tStrings('create')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default CreatePoolModal
