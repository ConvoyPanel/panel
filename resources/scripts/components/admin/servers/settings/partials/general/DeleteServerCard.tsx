import { AdminServerContext } from '@/state/admin/server'
import { useTranslation } from 'react-i18next'

import FormCard from '@/components/elements/FormCard'
import MessageBox from '@/components/elements/MessageBox'

import DeleteServerActions from '@/components/admin/servers/settings/partials/general/DeleteServerActions'


const DeleteServerCard = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)
    const { t } = useTranslation('admin.servers.settings')

    return (
        <FormCard className='w-full border-error'>
            <FormCard.Body>
                <FormCard.Title>{t('deletion.title')}</FormCard.Title>
                <div className='space-y-3 mt-3'>
                    <p className='description-small !text-foreground'>
                        {t('deletion.description')}
                    </p>
                    {server.status === 'deleting' && (
                        <MessageBox title='Warning' type='warning'>
                            {t('deletion.deleting_status')}
                        </MessageBox>
                    )}
                    {server.status === 'deletion_failed' && (
                        <MessageBox title='Warning' type='warning'>
                            {t('deletion.failed_status')}
                        </MessageBox>
                    )}
                </div>
            </FormCard.Body>
            <FormCard.Footer>
                <DeleteServerActions />
            </FormCard.Footer>
        </FormCard>
    )
}

export default DeleteServerCard
