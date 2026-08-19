import { createAnchor, updateAnchor } from '@/features/anchors/api.ts'
import EnrollmentPanel from '@/features/anchors/components/EnrollmentPanel.tsx'
import anchorStatus from '@/features/anchors/status.ts'
import { type Anchor, anchorSchema } from '@/features/anchors/types.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconAlertTriangle } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import type { z } from 'zod'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import {
    Collapsible,
    CollapsiblePanel,
    CollapsibleTrigger,
} from '@/components/ui/Collapsible'
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/Field'
import {
    Form,
    FormButton,
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { InputForm, SelectForm } from '@/components/ui/Forms'
import { RadioGroup, RadioGroupItem } from '@/components/ui/RadioGroup'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

type FormValues = z.infer<typeof anchorSchema>

interface Props {
    anchor: Anchor | 'new' | null
    /** The live list, so step 2 sees the anchor flip to enrolled. */
    anchors: Anchor[]
    close: () => void
    refresh: () => Promise<unknown>
}

const roles = [
    {
        value: 'agent' as const,
        title: 'Agent',
        description:
            'Sits beside your nodes and carries console traffic for them.',
    },
    {
        value: 'relay' as const,
        title: 'Relay',
        description:
            "A public hop other anchors route through when they can't be reached directly.",
    },
]

/**
 * Role decides the shape of the rest of the form, and each option needs a
 * sentence a `SelectItem` has nowhere to put — so it is a pair of radio cards.
 * The border, padding and selected tint all come from `FieldLabel` wrapping a
 * `Field` (see docs/card-design.md); keep the `Field` a direct child.
 */
const RoleField = ({ locked }: { locked: boolean }) => (
    <FormField
        name='mode'
        render={({ field }) => (
            <FormItem>
                <FormLabel>Role</FormLabel>
                <FormControl>
                    <RadioGroup
                        value={field.value}
                        onValueChange={field.onChange}
                        disabled={locked}
                        // A control that cannot be changed has to look it; the
                        // reason follows underneath.
                        className={locked ? 'opacity-60' : undefined}
                    >
                        {roles.map(role => (
                            <FieldLabel
                                key={role.value}
                                htmlFor={`anchor-role-${role.value}`}
                            >
                                <Field orientation='horizontal'>
                                    <RadioGroupItem
                                        id={`anchor-role-${role.value}`}
                                        value={role.value}
                                        disabled={locked}
                                    />
                                    <FieldContent>
                                        <FieldTitle>{role.title}</FieldTitle>
                                        <FieldDescription>
                                            {role.description}
                                        </FieldDescription>
                                    </FieldContent>
                                </Field>
                            </FieldLabel>
                        ))}
                    </RadioGroup>
                </FormControl>
                {locked && (
                    <FormDescription>
                        The Anchor's role can't be changed once it has been
                        enrolled.
                    </FormDescription>
                )}
                <FormMessage />
            </FormItem>
        )}
    />
)

/**
 * Registering an anchor and installing it are one task, so they are one dialog:
 * saving moves to step 2 rather than closing and leaving the install command
 * three clicks away in a row menu.
 */
const AnchorFormDialog = ({ anchor, anchors, close, refresh }: Props) => {
    const current = anchor === 'new' ? null : anchor
    const [created, setCreated] = useState<Anchor | null>(null)
    const form = useForm<FormValues>({
        resolver: zodResolver(anchorSchema),
        values: {
            name: current?.name ?? '',
            mode: current?.mode ?? 'agent',
            publicUrl: current?.publicUrl ?? '',
            panelUrlOverride: current?.panelUrlOverride ?? '',
            relayId: current?.relayId?.toString() ?? 'none',
        },
    })

    // The dialog outlives a single open, so step 2 has to be cleared on the way
    // out or the next "Add anchor" reopens onto the last install command.
    useEffect(() => {
        if (anchor === null) setCreated(null)
    }, [anchor])

    const save = useMutation({
        mutationFn: (data: FormValues) =>
            current ? updateAnchor(current.id, data) : createAnchor(data),
    })

    const submit = async (data: FormValues) => {
        try {
            const saved = await save.mutateAsync(data)
            await refresh()
            toast.add({
                title: `Anchor ${current ? 'updated' : 'created'}`,
                type: 'success',
            })

            if (current) {
                close()
            } else {
                setCreated(saved)
            }
        } catch (error) {
            handleFormErrors(error, form.setError)
            toast.add({ title: 'Failed to save anchor', type: 'error' })
        }
    }

    const mode = form.watch('mode')
    const publicUrl = form.watch('publicUrl')
    const roleLocked =
        current !== null && current.compatibility !== 'unenrolled'
    const movingLiveEndpoint =
        current !== null &&
        current.compatibility !== 'unenrolled' &&
        publicUrl !== current.publicUrl

    // Step 2 reads the anchor back out of the list so the heartbeat that lands
    // while it is open flips the panel to "Enrolled".
    const enrolling = created
        ? (anchors.find(item => item.id === created.id) ?? created)
        : null

    const title = enrolling
        ? `Install on ${enrolling.name}`
        : current
          ? `Edit ${current.name}`
          : 'Add anchor'
    const description = enrolling
        ? 'Step 2 of 2 · Run this on the machine.'
        : current
          ? anchorStatus(current).label
          : 'Step 1 of 2 · Tell the panel where this machine is.'

    return (
        <ResponsiveDialog
            open={anchor !== null}
            onOpenChange={open => !open && close()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>{title}</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        {description}
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                {enrolling ? (
                    <EnrollmentPanel
                        anchor={enrolling}
                        refresh={refresh}
                        onClose={close}
                    />
                ) : (
                    <Form {...form}>
                        <form onSubmit={form.handleSubmit(submit)}>
                            <ResponsiveDialogBody className='flex flex-col gap-5'>
                                <RoleField locked={roleLocked} />
                                <InputForm
                                    name='name'
                                    label='Name'
                                    placeholder='pve-lab-03'
                                />
                                <InputForm
                                    name='publicUrl'
                                    label='Connection URL'
                                    placeholder='https://anchor.example.com'
                                    description='Where the panel and your browser reach this anchor.'
                                />
                                {movingLiveEndpoint && (
                                    <Alert>
                                        <IconAlertTriangle className='size-4' />
                                        <AlertDescription>
                                            {current.name} is reachable at its
                                            current URL. Changing it here does
                                            not move the anchor — console
                                            traffic breaks until the daemon
                                            answers on the new address.
                                        </AlertDescription>
                                    </Alert>
                                )}
                                {/* Hidden rather than disabled for a relay: a
                                    relay cannot route through another relay, so
                                    there is no choice to grey out. A disabled
                                    control invites a click and then explains
                                    nothing. */}
                                {mode === 'agent' && (
                                    <SelectForm
                                        name='relayId'
                                        label='Route through'
                                        items={[
                                            {
                                                value: 'none',
                                                label: 'Direct connection',
                                            },
                                            ...anchors
                                                .filter(
                                                    item =>
                                                        item.mode === 'relay' &&
                                                        item.id !== current?.id
                                                )
                                                .map(item => ({
                                                    value: String(item.id),
                                                    label: item.name,
                                                })),
                                        ]}
                                    />
                                )}
                                {/* A split-horizon-DNS escape hatch. Shown by
                                    default it implies every install has to
                                    decide something it doesn't. */}
                                <Collapsible>
                                    <CollapsibleTrigger>
                                        Advanced
                                    </CollapsibleTrigger>
                                    <CollapsiblePanel>
                                        <div className='pt-3'>
                                            <InputForm
                                                name='panelUrlOverride'
                                                label='Panel URL override'
                                                placeholder='Optional'
                                                description='Where this anchor reaches the panel, when the panel URL does not resolve on its network. Leave blank to use the default.'
                                            />
                                        </div>
                                    </CollapsiblePanel>
                                </Collapsible>
                            </ResponsiveDialogBody>
                            <ResponsiveDialogFooter className='mt-4'>
                                <ResponsiveDialogClose
                                    render={
                                        <Button type='button' variant='outline'>
                                            Cancel
                                        </Button>
                                    }
                                />
                                <FormButton>
                                    {current ? 'Save' : 'Continue'}
                                </FormButton>
                            </ResponsiveDialogFooter>
                        </form>
                    </Form>
                )}
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AnchorFormDialog
