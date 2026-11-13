import { subuserQueries, updateSubuser } from '@/features/subusers/api.ts'
import PermissionPicker from '@/features/subusers/components/PermissionPicker.tsx'
import {
    type Subuser,
    type SubuserPermissionsInput,
    subuserPermissionsSchema,
} from '@/features/subusers/types.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'

import { queryClient } from '@/lib/query-client.ts'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
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

interface Props {
    server: string
    subuser: Subuser | null
    close: () => void
}

const EditSubuserDialog = ({ server, subuser, close }: Props) => {
    const form = useForm<SubuserPermissionsInput>({
        resolver: zodResolver(subuserPermissionsSchema),
        values: { permissions: subuser?.permissions ?? [] },
    })

    const { mutateAsync: save } = useMutation({
        mutationFn: (data: SubuserPermissionsInput) =>
            updateSubuser(server, subuser!.uuid, data),
    })

    const onSubmit = async (data: SubuserPermissionsInput) => {
        try {
            await save(data)
            await queryClient.invalidateQueries({
                queryKey: subuserQueries.all(server),
            })

            toast.add({ title: 'Access updated', type: 'success' })
            close()
        } catch (error) {
            handleFormErrors(error, form.setError)
            toast.add({ title: 'Failed to update access', type: 'error' })
        }
    }

    return (
        <ResponsiveDialog
            open={subuser !== null}
            onOpenChange={open => !open && close()}
        >
            <ResponsiveDialogContent className={'@container sm:max-w-2xl'}>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        {subuser?.name}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)}>
                        <ResponsiveDialogBody>
                            <PermissionPicker name={'permissions'} />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Save</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default EditSubuserDialog
