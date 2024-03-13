import { useFlashKey } from '@/util/useFlash'
import { FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'
import useSWRMutation from 'swr/mutation'

import deleteCoterm from '@/api/admin/coterms/deleteCoterm'
import { Coterm, CotermResponse } from '@/api/admin/coterms/getCoterms'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import MessageBox from '@/components/elements/MessageBox'
import Modal from '@/components/elements/Modal'


interface Props {
    coterm: Coterm | null
    onClose: () => void
    mutate: KeyedMutator<CotermResponse>
}

const DeleteCotermModal = ({ coterm, onClose, mutate }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.coterms.${coterm?.id}.delete`
    )

    const { trigger, isMutating } = useSWRMutation(
        ['admin.coterms.delete', coterm?.id],
        async () => {
            clearFlashes()
            try {
                await deleteCoterm(coterm!.id)

                mutate(data => {
                    if (!data) return data

                    return {
                        ...data,
                        items: data.items.filter(
                            item => item.id !== coterm!.id
                        ),
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
        <Modal open={Boolean(coterm)} onClose={onClose}>
            <Modal.Header>
                <Modal.Title>Delete {coterm?.name}?</Modal.Title>
            </Modal.Header>

            <form onSubmit={submit}>
                <Modal.Body>
                    {coterm ? (
                        coterm.nodesCount > 0 ? (
                            <MessageBox
                                className={'mb-5'}
                                type={'error'}
                                title={tStrings('error') ?? 'Error'}
                            >
                                Cannot delete an instance of Coterm with nodes
                                attached to it.
                            </MessageBox>
                        ) : null
                    ) : null}
                    <FlashMessageRender
                        className='mb-5'
                        byKey={`admin.coterms.${coterm?.id}.delete`}
                    />
                    <Modal.Description>
                        Are you sure you want to delete this instance of Coterm?
                        This action is irreversible.
                    </Modal.Description>
                </Modal.Body>

                <Modal.Actions>
                    <Modal.Action type='button' onClick={onClose}>
                        {tStrings('cancel')}
                    </Modal.Action>
                    <Modal.Action
                        type='submit'
                        disabled={coterm ? coterm.nodesCount > 0 : false}
                        loading={isMutating}
                    >
                        {tStrings('delete')}
                    </Modal.Action>
                </Modal.Actions>
            </form>
        </Modal>
    )
}

export default DeleteCotermModal