import { Handle } from '@/routers/router'
import styled from '@emotion/styled'
import { ChevronRightIcon } from '@heroicons/react/20/solid'
import {
    Breadcrumbs as MantineBreadcrumbs,
    BreadcrumbsProps as MantineBreadcrumbsProps,
} from '@mantine/core'
import { FC } from 'react'
import { Link, LinkProps, useMatches } from 'react-router-dom'
import tw from 'twin.macro'

export type BreadcrumbsProps = MantineBreadcrumbsProps

export interface CrumbProps extends LinkProps {
    active?: boolean
}

interface Breadcrumbs extends FC<BreadcrumbsProps> {
    Crumb: FC<CrumbProps>
    Generate: FC
}

const StyledBreadcrumbs = styled(MantineBreadcrumbs)`
    ${tw`py-4`}

    .mantine-Breadcrumbs-separator {
        ${tw`px-1.5 mx-0`}
    }
`

const Breadcrumbs: Breadcrumbs = props => (
    <StyledBreadcrumbs
        separator={<ChevronRightIcon className={'w-4 h-4 text-accent-500'} />}
        {...props}
    />
)

const Crumb = styled(Link)<CrumbProps>`
    ${tw`text-sm truncate text-accent-500 transition-colors`}

    ${({ active }) => (active ? tw`text-accent-800` : null)}

    &:hover {
        ${tw`text-accent-800`}
    }
`

Breadcrumbs.Crumb = props => <Crumb {...props} />

Breadcrumbs.Generate = () => {
    const matches = useMatches()
    const crumbs = matches
        .filter(match => Boolean((match.handle as Handle | undefined)?.crumb))
        .map(match => (match.handle as Handle).crumb(match.data))

    return (
        <Breadcrumbs>
            {crumbs.map((crumb, index) => (
                <Crumb to={crumb.to} key={crumb.to}>
                    {crumb.element}
                </Crumb>
            ))}
        </Breadcrumbs>
    )
}

export default Breadcrumbs
