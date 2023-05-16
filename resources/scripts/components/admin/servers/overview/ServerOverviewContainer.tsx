import ServerContentBlock from '@/components/admin/servers/ServerContentBlock'
import Button from '@/components/elements/Button'
import { AdminServerContext } from '@/state/admin/server'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

const ServerOverviewContainer = () => {
    const server = AdminServerContext.useStoreState(state => state.server.data!)

    const { t } = useTranslation('admin.servers.overview')
    const { t: tStrings } = useTranslation('strings')

    return (
        <ServerContentBlock title={tStrings('overview')}>
            <Link to={`/servers/${server.id}`}>
                <Button className='inline-flex items-center' as={'span'}>
                    {t('enter_server_console')}
                </Button>
            </Link>
        </ServerContentBlock>
    )
}

export default ServerOverviewContainer
