import { ServerContext } from '@/state/server'
import useNotify from '@/util/useNotify'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import updateStatus, { PowerAction } from '@/api/server/updateState'

import Button from '@/components/elements/Button'
import ConfirmationModal from '@/components/elements/ConfirmationModal'


const ServerPowerBlock = () => {
    const { t } = useTranslation('server.overview')
    const uuid = ServerContext.useStoreState(state => state.server.data?.uuid)
    const name = ServerContext.useStoreState(state => state.server.data?.name)
    const state = ServerContext.useStoreState(state => state.status.data?.state)
    const notify = useNotify()
    const [confirmingKill, setConfirmingKill] = useState(false)

    const update = (state: PowerAction) => {
        updateStatus(uuid!, state)
            .then(() =>
                notify({
                    message: t('notices.power_action_sent_success'),
                    color: 'green',
                })
            )
            .catch(() =>
                notify({
                    message: t('notices.power_action_sent_fail'),
                    color: 'red',
                })
            )
    }

    return (
        <div className='grid grid-cols-2 gap-3 xs:gap-0 xs:flex justify-end xs:space-x-3 mb-3'>
            <Button
                className='transition-colors'
                disabled={!state || state === 'running'}
                onClick={() => update('start')}
            >
                {t('power_actions.start')}
            </Button>
            <Button
                className='transition-colors'
                disabled={state !== 'running'}
                onClick={() => update('restart')}
            >
                {t('power_actions.restart')}
            </Button>
            <Button
                className='transition-colors'
                color='danger'
                variant='outline'
                disabled={!state || state === 'stopped'}
                onClick={() => setConfirmingKill(true)}
            >
                {t('power_actions.kill')}
            </Button>
            <Button
                className='transition-colors'
                color='danger'
                variant='filled'
                disabled={state !== 'running'}
                onClick={() => update('shutdown')}
            >
                {t('power_actions.shutdown')}
            </Button>
            <ConfirmationModal
                open={confirmingKill}
                title={t('kill_confirmation.title', { name })}
                description={t('kill_confirmation.description', { name })}
                confirmLabel={t('power_actions.kill')}
                onConfirm={() => {
                    setConfirmingKill(false)
                    update('kill')
                }}
                onClose={() => setConfirmingKill(false)}
            />
        </div>
    )
}

export default ServerPowerBlock