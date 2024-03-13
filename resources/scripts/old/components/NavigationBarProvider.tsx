import { ReactNode, useState } from 'react'

import {
    NavigationBarContext,
    RouteDefinition,
} from '@/components/elements/navigation/NavigationBar'

interface Props {
    children?: ReactNode
}

const NavigationBarProvider = ({ children }: Props) => {
    const [routes, setRoutes] = useState<RouteDefinition[]>([])
    const [breadcrumb, setBreadcrumb] = useState<string | null | undefined>()

    const value = {
        routes,
        setRoutes,
        breadcrumb,
        setBreadcrumb,
    }

    return (
        <NavigationBarContext.Provider value={value}>
            {children}
        </NavigationBarContext.Provider>
    )
}

export default NavigationBarProvider
