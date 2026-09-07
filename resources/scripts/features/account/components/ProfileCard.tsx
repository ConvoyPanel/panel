import AvatarCropDialog from '@/features/account/components/AvatarCropDialog.tsx'
import EmailChangeDialog from '@/features/account/components/EmailChangeDialog.tsx'
import {
    AVATAR_ACCEPT,
    AVATAR_MAX_BYTES,
    type AvatarCrop,
    profileSchema,
    removeAvatar,
    updateProfile,
    uploadAvatar,
} from '@/features/account/profile/api.ts'
import { currentUserQueries, useUser } from '@/features/auth/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { AuthenticatedUser } from '@/types/user.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useEffect, useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardFooter } from '@/components/ui/Card'
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldGroup,
    FieldSeparator,
    FieldTitle,
} from '@/components/ui/Field'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'
import UserAvatar from '@/components/ui/UserAvatar.tsx'

/**
 * Shown under a field the operator has locked. Deliberately vague about who
 * "the administrator" is and why: the panel knows the switch is off, not what
 * upstream system it was turned off for.
 */
const MANAGED_NOTE = 'Managed by your administrator.'

const ProfileCard = () => {
    const { data: user } = useUser()
    const mutate = useQueryMutator<AuthenticatedUser>(currentUserQueries.all())
    const input = useRef<HTMLInputElement>(null)
    const [cropping, setCropping] = useState<File | null>(null)

    const canChangeName = user?.accountCapabilities.canChangeName ?? true
    const canChangeEmail = user?.accountCapabilities.canChangeEmail ?? true
    const canChangeAvatar = user?.accountCapabilities.canChangeAvatar ?? true

    const form = useForm({
        resolver: zodResolver(profileSchema),
        defaultValues: { name: '' },
    })

    useEffect(() => {
        if (!user) return

        form.reset({ name: user.name })
    }, [form, user])

    const { mutateAsync: save } = useMutation({
        mutationFn: updateProfile,
        onSuccess: async updated => {
            await mutate(() => updated)
            toast.add({ title: 'Profile updated', type: 'success' })
        },
    })

    const { mutateAsync: upload, isPending: uploading } = useMutation({
        mutationFn: ({ file, crop }: { file: File; crop: AvatarCrop }) =>
            uploadAvatar(file, crop),
        onSuccess: async updated => {
            await mutate(() => updated)
            setCropping(null)
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
        if (!file) return

        // Checked here as well as on the server so a photo that is never going
        // to be accepted doesn't have to upload first to find out.
        if (file.size > AVATAR_MAX_BYTES) {
            toast.add({ title: 'Pictures must be under 10 MB', type: 'error' })

            return
        }

        setCropping(file)
    }

    const submit = async (data: z.infer<typeof profileSchema>) => {
        try {
            await save(data)
        } catch (e) {
            handleFormErrors(e, form.setError)

            throw e
        }
    }

    return (
        <>
            <Card>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        {/* No CardHeader: this is the only card on the page and
                            the heading above it already says Profile. */}
                        <CardContent className={'pt-4'}>
                            <FieldGroup>
                                <Field orientation={'responsive'}>
                                    <FieldContent>
                                        <FieldTitle>Picture</FieldTitle>
                                        <FieldDescription>
                                            {canChangeAvatar
                                                ? 'Shown beside your name. Cropped square and stored at 512px.'
                                                : MANAGED_NOTE}
                                        </FieldDescription>
                                    </FieldContent>
                                    {/* The picture itself stays whether or not
                                        it can be changed -- it is the account's
                                        own, and a locked field still has to say
                                        what it currently holds. */}
                                    <div className={'flex items-center gap-3'}>
                                        <UserAvatar
                                            name={user?.name}
                                            src={user?.avatarUrl}
                                            className={'size-12 text-sm'}
                                        />
                                        {canChangeAvatar && (
                                            <>
                                                <input
                                                    ref={input}
                                                    type={'file'}
                                                    accept={AVATAR_ACCEPT}
                                                    className={'hidden'}
                                                    onChange={event => {
                                                        choose(
                                                            event.target
                                                                .files?.[0]
                                                        )
                                                        // Cleared so picking
                                                        // the same file twice
                                                        // still fires a change
                                                        // event.
                                                        event.target.value = ''
                                                    }}
                                                />
                                                <Button
                                                    variant={'outline'}
                                                    type={'button'}
                                                    disabled={
                                                        uploading || removing
                                                    }
                                                    onClick={() =>
                                                        input.current?.click()
                                                    }
                                                >
                                                    Upload
                                                </Button>
                                                {user?.avatarUrl ? (
                                                    <Button
                                                        variant={'ghost'}
                                                        type={'button'}
                                                        loading={removing}
                                                        disabled={
                                                            uploading ||
                                                            removing
                                                        }
                                                        onClick={() =>
                                                            void remove().catch(
                                                                () => {}
                                                            )
                                                        }
                                                    >
                                                        Remove
                                                    </Button>
                                                ) : null}
                                            </>
                                        )}
                                    </div>
                                </Field>

                                <FieldSeparator />

                                {/* Locked still means shown: the name is the
                                    account's own, and hiding it would leave the
                                    card unable to say what it is called. */}
                                <InputForm
                                    name={'name'}
                                    label={'Display name'}
                                    orientation={'responsive'}
                                    disabled={!canChangeName}
                                    description={
                                        canChangeName
                                            ? 'Shown wherever your account appears.'
                                            : MANAGED_NOTE
                                    }
                                />

                                <FieldSeparator />

                                {/* Its own flow rather than a second field in
                                    this form: the address a password reset goes
                                    to is not something a live cookie alone may
                                    change, so the endpoint behind it is gated on
                                    a confirmed identity. */}
                                <Field orientation={'responsive'}>
                                    <FieldContent>
                                        <FieldTitle>Email</FieldTitle>
                                        <FieldDescription>
                                            {canChangeEmail
                                                ? user?.email
                                                : MANAGED_NOTE}
                                        </FieldDescription>
                                    </FieldContent>
                                    {canChangeEmail ? (
                                        <EmailChangeDialog
                                            trigger={
                                                <Button
                                                    variant={'outline'}
                                                    type={'button'}
                                                >
                                                    Change
                                                </Button>
                                            }
                                        />
                                    ) : (
                                        <span
                                            className={
                                                'text-muted-foreground text-sm'
                                            }
                                        >
                                            {user?.email}
                                        </span>
                                    )}
                                </Field>
                            </FieldGroup>
                        </CardContent>

                        {canChangeName && (
                            <CardFooter className={'justify-end'}>
                                <FormButton disabled={!form.formState.isDirty}>
                                    Save changes
                                </FormButton>
                            </CardFooter>
                        )}
                    </form>
                </Form>
            </Card>

            <AvatarCropDialog
                file={cropping}
                busy={uploading}
                onCancel={() => setCropping(null)}
                onConfirm={(file, crop) =>
                    void upload({ file, crop }).catch(() => {})
                }
            />
        </>
    )
}

export default ProfileCard
