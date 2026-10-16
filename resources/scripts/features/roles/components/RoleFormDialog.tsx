import {
    createAdminRole,
    roleQueries,
    updateAdminRole,
} from '@/features/roles/api.ts'
import {
    type AdminPermission,
    type AdminRole,
    type AdminRoleInput,
    adminPermissionDescriptions,
    adminPermissionGroups,
    adminPermissionLabels,
    adminRoleSchema,
} from '@/features/roles/types.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm, useFormContext } from 'react-hook-form'

import { queryClient } from '@/lib/query-client.ts'

import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import {
    Form,
    FormButton,
    FormDescription,
    FormItem,
    FormLabel,
} from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { Label } from '@/components/ui/Label'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

/**
 * What the dialog is doing. `'new'` opens an empty form; a role opens it for editing; a role plus
 * `duplicate` opens an empty-named copy of it, which is how an operator who needs Support plus
 * Network gets there without reassembling ten permissions from memory.
 */
export type RoleDialogTarget =
    | { mode: 'new' }
    | { mode: 'edit'; role: AdminRole }
    | { mode: 'duplicate'; role: AdminRole }

interface Props {
    target: RoleDialogTarget | null
    close: () => void
}

const PermissionPicker = () => {
    const form = useFormContext<AdminRoleInput>()
    const selected = form.watch('permissions') ?? []

    const toggle = (permission: AdminPermission, checked: boolean) =>
        form.setValue(
            'permissions',
            checked
                ? [...selected, permission]
                : selected.filter(value => value !== permission),
            { shouldDirty: true }
        )

    return (
        <div className={'flex flex-col gap-4'}>
            {adminPermissionGroups.map(group => (
                <div key={group.label} className={'flex flex-col gap-2'}>
                    <Label className={'text-muted-foreground text-xs'}>
                        {group.label}
                    </Label>
                    <div className={'grid gap-2 @md:grid-cols-2'}>
                        {group.permissions.map(permission => (
                            <FormItem
                                key={permission}
                                className={
                                    'flex-row items-start gap-3 rounded-lg border p-3 shadow-xs'
                                }
                            >
                                <Checkbox
                                    id={`permission-${permission}`}
                                    checked={selected.includes(permission)}
                                    onCheckedChange={checked =>
                                        toggle(permission, checked === true)
                                    }
                                    disabled={form.formState.isSubmitting}
                                />
                                <div
                                    className={
                                        'flex flex-col gap-1 leading-none'
                                    }
                                >
                                    <FormLabel
                                        htmlFor={`permission-${permission}`}
                                    >
                                        {adminPermissionLabels[permission]}
                                    </FormLabel>
                                    {adminPermissionDescriptions[
                                        permission
                                    ] && (
                                        <FormDescription>
                                            {
                                                adminPermissionDescriptions[
                                                    permission
                                                ]
                                            }
                                        </FormDescription>
                                    )}
                                </div>
                            </FormItem>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    )
}

const RoleFormDialog = ({ target, close }: Props) => {
    const role = target && target.mode !== 'new' ? target.role : null
    const editing = target?.mode === 'edit' ? target.role : null

    const form = useForm<AdminRoleInput>({
        resolver: zodResolver(adminRoleSchema),
        values: {
            name:
                target?.mode === 'duplicate'
                    ? `${role!.name} copy`
                    : (editing?.name ?? ''),
            description: role?.description ?? null,
            permissions: role?.permissions ?? [],
        },
    })

    const { mutateAsync: save } = useMutation({
        mutationFn: (data: AdminRoleInput) =>
            editing
                ? updateAdminRole(editing.uuid, data)
                : createAdminRole(data),
    })

    const submit = async (data: AdminRoleInput) => {
        try {
            await save(data)
            await queryClient.invalidateQueries({ queryKey: roleQueries.all() })

            toast.add({
                title: editing ? 'Role updated' : 'Role created',
                type: 'success',
            })
            close()
        } catch (error) {
            handleFormErrors(error, form.setError)
            toast.add({ title: 'Failed to save role', type: 'error' })
        }
    }

    return (
        <ResponsiveDialog
            open={target !== null}
            onOpenChange={open => !open && close()}
        >
            <ResponsiveDialogContent className={'@container sm:max-w-2xl'}>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {editing ? `Edit ${editing.name}` : 'Add role'}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'flex flex-col gap-5'}>
                            <InputForm
                                name={'name'}
                                label={'Name'}
                                autoComplete={'off'}
                            />
                            <InputForm
                                name={'description'}
                                label={'Description'}
                                autoComplete={'off'}
                            />
                            {editing?.isSystem ? (
                                <p className={'text-muted-foreground text-sm'}>
                                    A built-in role grants a fixed set of
                                    permissions. Duplicate it to change them.
                                </p>
                            ) : (
                                <PermissionPicker />
                            )}
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>
                                {editing ? 'Save' : 'Add role'}
                            </FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default RoleFormDialog
