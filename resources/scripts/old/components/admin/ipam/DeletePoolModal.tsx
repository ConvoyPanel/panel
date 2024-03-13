import { useFlashKey } from '@/util/useFlash'
import { FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'
import useSWRMutation from 'swr/mutation'

import deleteAddressPool from '@/api/admin/addressPools/deleteAddressPool'
import {
    AddressPool,
    AddressPoolResponse,
} from '@/api/admin/addressPools/getAddressPools'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import MessageBox from '@/components/elements/MessageBox'
import Modal from '@/components/elements/Modal'


interface Props {
    pool: AddressPool | null
    onClose: () => void
    mutate: KeyedMutator<AddressPoolResponse>
}

const CreatePoolModal = ({ pool, onClose, mutate }: Props) => {
    const { t } = useTranslation('admin.addressPools.index')
    const { t: tStrings } = useTranslation('strings')
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.addressPools.${pool?.id}.delete`
    )
    const { trigger, isMutating } = useSWRMutation(
        ['admin.address-pools.delete', pool?.id],
        async () => {
            clearFlashes()
            try {
                await deleteAddressPool(pool!.id)

                mutate(data => {
                    if (!data) return data

                    return {
                        ...data,
                        items: data.items.filter(item => item.id !== pool!.id),
                    }
                }, false)

                onClose()
            } catch (e) {
                clearAndAddHttpError(e as Error)
                throw e
            }
        }
    )

    const submit = (e: FormEvent) => {
        e.preventDefault()
        trigger()
    }

    return (
        <Modal open={Boolean(pool)} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>
                    {t('delete_modal.title', { name: pool?.name })}
                </Modal.Title>
            </Modal.Header>

            <form onSubmit={submit}>
                <Modal.Body>
                    {pool ? (
                        pool.nodesCount > 0 ? (
                            <MessageBox
                                className={'mb-5'}
                                type={'error'}
                                title={tStrings('error') ?? 'Error'}
                            >
                                {t('delete_modal.nodes_linked_error')}
                            </MessageBox>
                        ) : null
                    ) : null}
                    <FlashMessageRender
                        className='mb-5'
                        byKey={`admin.addressPools.${pool?.id}.delete`}
                    />
                    <Modal.Description>
                        {t('delete_modal.description')}
                    </Modal.Description>
                </Modal.Body>

                <Modal.Actions>
                    <Modal.Action type='button' onClick={onClose}>
                        {tStrings('cancel')}
                    </Modal.Action>
                    <Modal.Action
                        type='submit'
                        disabled={pool ? pool.nodesCount > 0 : false}
                        loading={isMutating}
                    >
                        {tStrings('delete')}
                    </Modal.Action>
                </Modal.Actions>
            </form>
        </Modal>
    )
}

export default CreatePoolModal