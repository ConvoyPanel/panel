import AvatarCard from '@/features/account/components/AvatarCard.tsx'
import ProfileCard from '@/features/account/components/ProfileCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'

const Profile = () => (
    <>
        <Heading>Profile</Heading>
        <div className={'grid grid-cols-1 items-start gap-5 @3xl:grid-cols-2'}>
            <AvatarCard />
            <ProfileCard />
        </div>
    </>
)

export const Route = createLazyFileRoute('/_app/account/')({
    component: Profile,
})
