import { Route as ClientDashboardRoute } from '@/routes/_app/_dashboard/route.tsx'
import { Route as AdminDashboardRoute } from '@/routes/_app/admin/(dashboard)/route.tsx'
import { Link, useLocation, useRouter } from '@tanstack/react-router'

import ErrorPageLayout, {
    ErrorCode,
    ErrorDescription,
    ErrorFooter,
    ErrorTitle,
} from '@/components/layouts/ErrorPageLayout.tsx'

import { Button, buttonVariants } from '@/components/ui/Button'

const NotFoundComponent = () => {
    const router = useRouter()
    const isAdmin = useLocation({
        select: location => location.pathname,
    }).startsWith('/admin')

    return (
        <ErrorPageLayout>
            <ErrorCode>404</ErrorCode>
            <ErrorTitle>Page not found</ErrorTitle>
            <ErrorDescription>
                Sorry, we couldn’t find the page you’re looking for.
            </ErrorDescription>
            <ErrorFooter>
                <Button onClick={() => router.history.back()}>Go back</Button>
                <Link
                    to={
                        isAdmin
                            ? AdminDashboardRoute.to
                            : ClientDashboardRoute.to
                    }
                    className={buttonVariants({
                        variant: 'ghost',
                    })}
                >
                    Home
                </Link>
            </ErrorFooter>
        </ErrorPageLayout>
    )
}

export default NotFoundComponent
