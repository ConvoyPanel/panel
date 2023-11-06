import { ServerContext } from '@/state/server'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import Button from '@/components/elements/Button'
import Card from '@/components/elements/Card'


const ServerAdminBlock = () => {
    const serverId = ServerContext.useStoreState(state => state.server.data!.id)
    const { t } = useTranslation('server.overview')

    return (
        <Card className='flex flex-col justify-between col-span-10 md:col-span-5 relative'>
            <div>
                <h5 className='h5'>{t('server_config.title')}</h5>
                <p className='description-small mt-1'>
                    {t('server_config.description')}
                </p>
            </div>
            <div className='flex mt-6 justify-end'>
                <Link to={`/admin/servers/${serverId}`}>
                    <Button className='inline-flex items-center' as={'span'}>
                        {t('server_config.configure_server')}
                    </Button>
                </Link>
            </div>
        </Card>
    )
}

export default ServerAdminBlock