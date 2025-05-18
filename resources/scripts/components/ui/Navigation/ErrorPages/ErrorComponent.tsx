import { Route as ClientDashboardRoute } from '@/routes/_app/_dashboard.tsx'
import { Route as AdminDashboardRoute } from '@/routes/_app/admin/_dashboard.tsx'
import {
    ErrorComponentProps,
    Link,
    useLocation,
    useRouter,
} from '@tanstack/react-router'

import ErrorPageLayout, {
    ErrorCode,
    ErrorDescription,
    ErrorFooter,
    ErrorTitle,
} from '@/components/layouts/ErrorPageLayout.tsx'

import { Button, buttonVariants } from '@/components/ui/Button'

const ErrorComponent = ({ error, reset }: ErrorComponentProps) => {
    const router = useRouter()
    const isAdmin = useLocation({
        select: location => location.pathname,
    }).startsWith('/admin')

    console.log({ error })

    return (
        <ErrorPageLayout>
            <ErrorCode>Error</ErrorCode>
            <ErrorTitle>Something went wrong</ErrorTitle>
            <ErrorDescription>{error.message}</ErrorDescription>
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
                <Button onClick={reset} variant={'ghost'}>
                    Retry
                </Button>
            </ErrorFooter>
        </ErrorPageLayout>
    )
}

export default ErrorComponent
