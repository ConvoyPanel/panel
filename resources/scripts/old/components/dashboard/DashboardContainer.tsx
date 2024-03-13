import PageContentBlock from '@/components/elements/PageContentBlock'

import ServersContainer from '@/components/dashboard/ServersContainer'

const DashboardContainer = () => {
    return (
        <PageContentBlock title='Dashboard' showFlashKey='dashboard'>
            {/*<EntityGroup>*/}
            {/*    <Entity>*/}
            {/*        <EntityField title='Backup 1' width={'80%'} />*/}
            {/*        <EntityField description='0' />*/}
            {/*        <EntityField description='0' />*/}
            {/*    </Entity>*/}
            {/*</EntityGroup>*/}
            <ServersContainer />
        </PageContentBlock>
    )
}

export default DashboardContainer
