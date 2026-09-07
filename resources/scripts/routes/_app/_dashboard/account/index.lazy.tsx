import ProfileCard from '@/features/account/components/ProfileCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'

const Profile = () => (
    <>
        <Heading>Profile</Heading>
        {/* Capped rather than full-bleed: the fields are settings rows, and a
            label/control pair stretched across 1100px reads as two columns
            that have nothing to do with each other. */}
        <div className={'max-w-3xl'}>
            <ProfileCard />
        </div>
    </>
)

export const Route = createLazyFileRoute('/_app/_dashboard/account/')({
    component: Profile,
})
