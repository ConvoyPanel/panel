import { Link, useRouterState } from '@tanstack/react-router'
import React from 'react'

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/Breadcrumb'

const Breadcrumbs = () => {
    const breadcrumbs = useRouterState({
        select: state => {
            return state.matches
                .map(match => ({
                    title: match.meta?.find(tag => tag.title)!.title as string,
                    path: match.pathname,
                }))
                .filter(crumb => Boolean(crumb.title))
        },
    })

    return (
        <Breadcrumb className='hidden md:flex'>
            <BreadcrumbList>
                {breadcrumbs.map((breadcrumb, index) => (
                    <React.Fragment key={breadcrumb.path}>
                        <BreadcrumbItem>
                            {index === breadcrumbs.length - 1 ? (
                                <BreadcrumbPage>
                                    {breadcrumb.title}
                                </BreadcrumbPage>
                            ) : (
                                <BreadcrumbLink asChild>
                                    <Link to={breadcrumb.path}>
                                        {breadcrumb.title}
                                    </Link>
                                </BreadcrumbLink>
                            )}
                        </BreadcrumbItem>
                        {index < breadcrumbs.length - 1 && (
                            <BreadcrumbSeparator />
                        )}
                    </React.Fragment>
                ))}
            </BreadcrumbList>
        </Breadcrumb>
    )
}

export default Breadcrumbs
