import ServersContainer from '@/components/dashboard/ServersContainer'
import Button from '@/components/elements/Button'
import Entity from '@/components/elements/displays/Entity'
import EntityField from '@/components/elements/displays/EntityField'
import EntityGroup from '@/components/elements/displays/EntityGroup'
import PageContentBlock from '@/components/elements/PageContentBlock'

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
