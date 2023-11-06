import styled from '@emotion/styled'
import { Loader } from '@mantine/core'
import tw from 'twin.macro'

import Button from '@/components/elements/Button'
import Logo from '@/components/elements/Logo'
import LogoOutline from '@/components/elements/LogoOutline'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Entity from '@/components/elements/displays/Entity'
import EntityField from '@/components/elements/displays/EntityField'
import EntityGroup from '@/components/elements/displays/EntityGroup'

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
