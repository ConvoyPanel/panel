import { bindUrlParams } from '@/util/helpers'
import styled from '@emotion/styled'
import { ChevronLeftIcon } from '@heroicons/react/20/solid'
import { useViewportSize } from '@mantine/hooks'
import { ComponentType, Fragment, ReactNode, useEffect } from 'react'
import {
    Link,
    Outlet,
    useMatch,
    useMatches,
    useNavigate,
} from 'react-router-dom'
import tw from 'twin.macro'

import { RouteDefinition } from '@/components/elements/navigation/NavigationBar'
import VerticalNavTab from '@/components/elements/navigation/VerticalNavTab'

interface Props {
    indexPattern: string
    defaultUrl: string
    contentBlock?: ComponentType<{ children: ReactNode }>
    routes: RouteDefinition[]
}

const Grid = styled.div`
    ${tw`grid`}

    @media (min-width: 960px) {
        ${tw`gap-16 grid-cols-4`}
    }
    @media (max-width: 960px) {
        ${tw`grid-cols-1`}
    }
`

const VerticalNavContainer = styled.div`
    ${tw`flex flex-col`}

    @media (max-width: 960px) {
        ${tw`divide-y divide-accent-200`}

        & > a:is(:first-of-type) {
            ${tw`pt-0`}
        }
    }
`

const ReturnLink = styled(Link)`
    ${tw`flex items-center space-x-3 pb-6 text-foreground text-foreground font-semibold text-sm`}
`

const SettingsLayout = ({
    indexPattern,
    defaultUrl,
    contentBlock: ContentBlock,
    routes,
}: Props) => {
    const matches = useMatches()
    const { width } = useViewportSize()
    const isIndex = useMatch(indexPattern)
    const navigate = useNavigate()

    useEffect(() => {
        if (width > 960 && isIndex) {
            navigate(
                bindUrlParams(defaultUrl, matches[matches.length - 1].params),
                {
                    replace: true,
                }
            )
        }
    }, [isIndex, width])

    const Wrapper = ContentBlock ?? Fragment

    return (
        <div className='bg-background min-h-screen'>
            <Wrapper>
                <Grid className={isIndex ? 'border-b border-accent-200' : ''}>
                    {width > 960 || Boolean(isIndex) ? (
                        <VerticalNavContainer>
                            {routes.map(route => (
                                <VerticalNavTab
                                    key={route.name}
                                    to={bindUrlParams(
                                        route.path,
                                        matches[matches.length - 1].params
                                    )}
                                    end={route.end}
                                >
                                    {route.name}
                                </VerticalNavTab>
                            ))}
                        </VerticalNavContainer>
                    ) : null}
                    <div className=' col-span-3'>
                        {width <= 960 && !Boolean(isIndex) ? (
                            <ReturnLink
                                to={bindUrlParams(
                                    indexPattern,
                                    matches[matches.length - 1].params
                                )}
                            >
                                <ChevronLeftIcon className='w-5 h-5' />
                                <span>Settings</span>
                            </ReturnLink>
                        ) : null}
                        <div className='space-y-8'>
                            <Outlet />
                        </div>
                    </div>
                </Grid>
            </Wrapper>
        </div>
    )
}

export default SettingsLayout
