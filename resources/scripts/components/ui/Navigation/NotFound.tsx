import { Route as DashboardRoute } from '@/routes/_app/_dashboard.tsx'
import { Link, useRouter } from '@tanstack/react-router'

import { Button, buttonVariants } from '@/components/ui/Button'
import Lighthouse from '@/components/ui/Illustrations/Lighthouse.tsx'


const NotFound = () => {
    const router = useRouter()

    return (
        <div
            className={
                'relative flex h-full w-full flex-col items-center justify-center'
            }
        >
            <Lighthouse
                className={
                    'fixed bottom-4 -right-2 z-0 w-[20rem] text-muted-foreground/10 md:w-[40rem]'
                }
            />
            <h1 className={'text-sm font-semibold'}>404</h1>
            <h2 className={'mt-2 text-4xl font-bold tracking-tight'}>
                Page not found
            </h2>
            <p className={'mt-4 text-muted-foreground'}>
                Sorry, we couldn’t find the page you’re looking for.
            </p>
            <div className={'mt-8 flex gap-3'}>
                <Button onClick={() => router.history.back()}>Go back</Button>
                <Link
                    to={DashboardRoute.to}
                    className={buttonVariants({
                        variant: 'ghost',
                    })}
                >
                    Home
                </Link>
            </div>
        </div>
    )
}

export default NotFound
