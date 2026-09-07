import {
    AVATAR_ACCEPT,
    AVATAR_MAX_BYTES,
    removeAvatar,
    uploadAvatar,
} from '@/features/account/profile/api.ts'
import { currentUserQueries, useUser } from '@/features/auth/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { AuthenticatedUser } from '@/types/user.ts'
import { useMutation } from '@tanstack/react-query'
import { useRef } from 'react'

import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { toast } from '@/components/ui/Toast'
import UserAvatar from '@/components/ui/UserAvatar.tsx'

const AvatarCard = () => {
    const { data: user } = useUser()
    const mutate = useQueryMutator<AuthenticatedUser>(currentUserQueries.all())
    const input = useRef<HTMLInputElement>(null)

    const { mutateAsync: upload, isPending: uploading } = useMutation({
        mutationFn: uploadAvatar,
        onSuccess: async updated => {
            await mutate(() => updated)
            toast.add({ title: 'Picture updated', type: 'success' })
        },
        onError: () =>
            toast.add({ title: 'Failed to upload the picture', type: 'error' }),
    })

    const { mutateAsync: remove, isPending: removing } = useMutation({
        mutationFn: removeAvatar,
        onSuccess: async updated => {
            await mutate(() => updated)
            toast.add({ title: 'Picture removed', type: 'success' })
        },
        onError: () =>
            toast.add({ title: 'Failed to remove the picture', type: 'error' }),
    })

    const choose = (file?: File) => {
        // Checked here as well as on the server so a phone photo that is never
        // going to be accepted doesn't have to upload first to find out.
        if (!file) return

        if (file.size > AVATAR_MAX_BYTES) {
            toast.add({ title: 'Pictures must be under 10 MB', type: 'error' })

            return
        }

        void upload(file).catch(() => {})
    }

    const busy = uploading || removing

    return (
        <Card>
            <CardHeader>
                <CardTitle>Picture</CardTitle>
            </CardHeader>
            <CardContent className={'flex items-center gap-5'}>
                <UserAvatar
                    name={user?.name}
                    src={user?.avatarUrl}
                    className={'size-20 text-xl'}
                />
                <div className={'flex flex-wrap gap-2'}>
                    <input
                        ref={input}
                        type={'file'}
                        accept={AVATAR_ACCEPT}
                        className={'hidden'}
                        onChange={event => {
                            choose(event.target.files?.[0])
                            // Cleared so picking the same file twice still fires
                            // a change event.
                            event.target.value = ''
                        }}
                    />
                    <Button
                        variant={'outline'}
                        loading={uploading}
                        disabled={busy}
                        onClick={() => input.current?.click()}
                    >
                        Upload
                    </Button>
                    {user?.avatarUrl ? (
                        <Button
                            variant={'ghost'}
                            loading={removing}
                            disabled={busy}
                            onClick={() => void remove().catch(() => {})}
                        >
                            Remove
                        </Button>
                    ) : null}
                </div>
            </CardContent>
        </Card>
    )
}

export default AvatarCard
