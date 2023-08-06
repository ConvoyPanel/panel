import { AddressPool, AddressPoolResponse } from '@/api/admin/addressPools/getAddressPools'
import { KeyedMutator } from 'swr'
import { useTranslation } from 'react-i18next'
import { useFlashKey } from '@/util/useFlash'
import updateAddressPool from '@/api/admin/addressPools/updateAddressPool'
import { z } from 'zod'
import { FormProvider, useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import Modal from '@/components/elements/Modal'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import TextInputForm from '@/components/elements/forms/TextInputForm'
import { useEffect, useMemo } from 'react'

interface Props {
    pool: AddressPool | null
    onClose: () => void
    mutate: KeyedMutator<AddressPoolResponse>
}

const EditPoolModal = ({ pool, onClose, mutate }: Props) => {
    const { t } = useTranslation('admin.addressPools.index')
    const { t: tStrings } = useTranslation('strings')
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(`admin.addressPools.${pool?.id}.update`)

    const schema = z.object({
        name: z.string().nonempty().max(191),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
        },
    })

    useEffect(() => {
        form.reset({
            name: pool?.name ?? '',
        })
    }, [pool])

    const handleClose = () => {
        form.reset()
        onClose()
    }

    const submit = async (data: z.infer<typeof schema>) => {
        clearFlashes()
        try {
            const updatedPool = await updateAddressPool(pool!.id, data)

            mutate(data => {
                if (!data) return data

                return {
                    ...data,
                    items: data.items.map(item => (item.id === updatedPool.id ? updatedPool : item)),
                }
            }, false)

            handleClose()
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    return (
        <Modal open={Boolean(pool)} onClose={handleClose}>
            <Modal.Header>
                <Modal.Title>{t('edit_modal.title', { name: pool?.name })}</Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender className='mb-5' byKey={`admin.addressPools.${pool?.id}.update`} />
                        <TextInputForm name='name' label={tStrings('name')} />
                    </Modal.Body>

                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            {tStrings('cancel')}
                        </Modal.Action>
                        <Modal.Action type='submit' loading={form.formState.isSubmitting}>
                            {tStrings('save')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default EditPoolModal
