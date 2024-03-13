import { useFlashKey } from '@/util/useFlash'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'
import { z } from 'zod'

import {
    AddressPool,
    AddressPoolResponse,
} from '@/api/admin/addressPools/getAddressPools'
import updateAddressPool from '@/api/admin/addressPools/updateAddressPool'
import useAddressPoolNodesSWR from '@/api/admin/addressPools/useAddressPoolNodesSWR'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import NodesMultiSelectForm from '@/components/admin/ipam/NodesMultiSelectForm'


interface Props {
    pool: AddressPool | null
    onClose: () => void
    mutate: KeyedMutator<AddressPoolResponse>
}

const EditPoolModal = ({ pool, onClose, mutate }: Props) => {
    const { t } = useTranslation('admin.addressPools.index')
    const { t: tStrings } = useTranslation('strings')
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.addressPools.${pool?.id}.update`
    )
    const { data: nodes, isLoading: isLoadingNodes } = useAddressPoolNodesSWR(
        pool?.id ?? -1,
        {}
    )

    const schema = z.object({
        name: z.string().min(1).max(191),
        nodeIds: z.array(z.coerce.number()),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            nodeIds: [] as string[],
        },
    })

    useEffect(() => {
        form.reset({
            nodeIds: nodes?.items.map(node => node.id.toString()) ?? [],
        })
    }, [nodes])

    useEffect(() => {
        form.reset({
            name: pool?.name ?? '',
            nodeIds: nodes?.items.map(node => node.id.toString()) ?? [],
        })
    }, [pool])

    const handleClose = () => {
        form.reset()
        onClose()
    }

    const submit = async (_data: any) => {
        const data = _data as z.infer<typeof schema>

        clearFlashes()
        try {
            const updatedPool = await updateAddressPool(pool!.id, data)

            mutate(data => {
                if (!data) return data

                return {
                    ...data,
                    items: data.items.map(item =>
                        item.id === updatedPool.id ? updatedPool : item
                    ),
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
                <Modal.Title>
                    {t('edit_modal.title', { name: pool?.name })}
                </Modal.Title>
            </Modal.Header>

            <FormProvider {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Modal.Body>
                        <FlashMessageRender
                            className='mb-5'
                            byKey={`admin.addressPools.${pool?.id}.update`}
                        />
                        <TextInputForm name='name' label={tStrings('name')} />
                        <NodesMultiSelectForm loading={isLoadingNodes} />
                    </Modal.Body>

                    <Modal.Actions>
                        <Modal.Action type='button' onClick={handleClose}>
                            {tStrings('cancel')}
                        </Modal.Action>
                        <Modal.Action
                            type='submit'
                            loading={form.formState.isSubmitting}
                        >
                            {tStrings('save')}
                        </Modal.Action>
                    </Modal.Actions>
                </form>
            </FormProvider>
        </Modal>
    )
}

export default EditPoolModal