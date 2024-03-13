import { AdminServerContext } from '@/state/admin/server'
import { useFlashKey } from '@/util/useFlash'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import suspendServer from '@/api/admin/servers/suspendServer'
import unsuspendServer from '@/api/admin/servers/unsuspendServer'

import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import MessageBox from '@/components/elements/MessageBox'


const ServerSuspensionCard = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const setServer = AdminServerContext.useStoreActions(
        actions => actions.server.setServer
    )
    const { clearFlashes, clearAndAddHttpError } = useFlashKey(
        `admin.servers.${server.uuid}.settings.general.suspension`
    )
    const { t } = useTranslation('admin.servers.settings')
    const { t: tStrings } = useTranslation('strings')
    const [loading, setLoading] = useState(false)

    const handle = async () => {
        clearFlashes()
        setLoading(true)
        try {
            if (server.status === 'suspended') {
                await unsuspendServer(server.uuid)
                setServer({
                    ...server,
                    status: null,
                })
            } else {
                await suspendServer(server.uuid)
                setServer({
                    ...server,
                    status: 'suspended',
                })
            }
        } catch (error) {
            clearAndAddHttpError(error as any)
        }
        setLoading(false)
    }

    return (
        <FormCard className='w-full'>
            <FormCard.Body>
                <FormCard.Title>{t('suspension.title')}</FormCard.Title>
                <div className='space-y-3 mt-3'>
                    <FlashMessageRender
                        byKey={`admin.servers.${server.uuid}.settings.general.suspension`}
                    />
                    <p className='description-small !text-foreground'>
                        {t('suspension.description')}
                    </p>

                    <MessageBox
                        title={tStrings('status') ?? 'Status'}
                        type={
                            server.status === 'suspended' ? 'error' : 'success'
                        }
                    >
                        {server.status === 'suspended'
                            ? t('suspension.statuses.suspended')
                            : t('suspension.statuses.not_suspended')}
                    </MessageBox>
                </div>
            </FormCard.Body>
            <FormCard.Footer>
                <Button
                    loading={loading}
                    onClick={handle}
                    variant='filled'
                    color='success'
                    size='sm'
                >
                    {server.status === 'suspended'
                        ? t('suspension.unsuspend')
                        : t('suspension.suspend')}
                </Button>
            </FormCard.Footer>
        </FormCard>
    )
}

export default ServerSuspensionCard