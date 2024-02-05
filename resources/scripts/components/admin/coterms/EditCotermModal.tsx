import {useFlashKey} from '@/util/useFlash'
import {port} from '@/util/validation'
import {zodResolver} from '@hookform/resolvers/zod'
import {useEffect} from 'react'
import {FormProvider, useForm} from 'react-hook-form'
import {useTranslation} from 'react-i18next'
import {KeyedMutator} from 'swr'
import {z} from 'zod'

import {Coterm, CotermResponse} from '@/api/admin/coterms/getCoterms'
import updateCoterm from '@/api/admin/coterms/updateCoterm'
import useAttachedNodes from '@/api/admin/coterms/useAttachedNodes'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import CotermNodesMultiSelectForm from '@/components/admin/coterms/CotermNodesMultiSelect'


interface Props {
    coterm: Coterm | null
    onClose: () => void
    mutate: KeyedMutator<CotermResponse>
}

const EditCotermModal = ({coterm, onClose, mutate}: Props) => {
    const {t: tStrings} = useTranslation('strings')
    const {clearFlashes, clearAndAddHttpError} = useFlashKey(
            `admin.coterms.${coterm?.id}.update`
    )
    const {data: attachedNodes} = useAttachedNodes(coterm ? coterm.id : -1, {})

    const schema = z.object({
        name: z.string().min(1).max(191),
        isTlsEnabled: z.boolean(),
        fqdn: z.string().min(1).max(191),
        port: port(z.coerce.number()),
        nodeIds: z.array(z.coerce.number()),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            isTlsEnabled: false,
            fqdn: '',
            port: 443,
            nodeIds: [] as string[],
        },
    })

    useEffect(() => {
        form.reset(old => ({
            name: coterm?.name,
            isTlsEnabled: coterm?.isTlsEnabled,
            fqdn: coterm?.fqdn,
            port: coterm?.port,
            nodeIds: old.nodeIds,
        }))
    }, [coterm])

    useEffect(() => {
        form.reset(old => ({
            name: old.name,
            isTlsEnabled: old.isTlsEnabled,
            fqdn: old.fqdn,
            port: old.port,
            nodeIds: attachedNodes ? attachedNodes.items.map(node => node.id.toString()) : [],
        }))
    }, [attachedNodes])

    const handleClose = () => {
        form.reset()
        onClose()
    }

    const submit = async (_data: any) => {
        const data = _data as z.infer<typeof schema>

        clearFlashes()
        try {
            const updatedCoterm = await updateCoterm(coterm!.id, data)
            mutate(data => {
                if (!data) return data

                return {
                    ...data,
                    items: data.items.map(item =>
                            item.id === updatedCoterm.id ? updatedCoterm : item
                    ),
                }
            }, false)
            handleClose()
        } catch (e) {
            clearAndAddHttpError(e)
        }
    }

    return (
            <Modal open={Boolean(coterm)} onClose={handleClose}>
                <Modal.Header>
                    <Modal.Title>Edit {coterm?.name}</Modal.Title>
                </Modal.Header>

                <FormProvider {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <Modal.Body>
                            <FlashMessageRender
                                    className='mb-5'
                                    byKey={`admin.coterms.${coterm?.id}.update`}
                            />
                            <TextInputForm name='name' label={tStrings('name')}/>
                            <div className={'space-y-3'}>
                                <TextInputForm
                                        name='fqdn'
                                        label={tStrings('fqdn')}
                                />
                                <CheckboxForm
                                        name={'isTlsEnabled'}
                                        label={'Is TLS Enabled?'}
                                />
                            </div>
                            <TextInputForm name='port' label={tStrings('port')}/>
                            <CotermNodesMultiSelectForm/>
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

export default EditCotermModal