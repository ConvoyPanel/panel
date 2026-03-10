import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { toast } from 'sonner';
import { IconPlus } from '@tabler/icons-react';
import { Button } from '@/components/ui/Button';
import { Form } from '@/components/ui/Form';
import FormButton from '@/components/ui/Form/FormButton';
import InputForm from '@/components/ui/Forms/InputForm';
import CheckboxItemForm from '@/components/ui/Forms/CheckboxItemForm';
import {
    Credenza,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza';
import createSnapshot from '@/api/servers/snapshots/createSnapshot';

const createSnapshotSchema = z.object({
    name: z.string().min(1, 'Name is required').max(40).regex(/^[a-zA-Z0-9_-]+$/, 'Alphanumeric, dashes, underscores only'),
    description: z.string().max(191).optional(),
    includesRam: z.boolean().default(false),
});

type CreateSnapshotForm = z.infer<typeof createSnapshotSchema>;

interface Props {
    serverUuid: string;
    onSuccess: () => void;
}

export default function CreateSnapshotModal({ serverUuid, onSuccess }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm<CreateSnapshotForm>({
        resolver: zodResolver(createSnapshotSchema),
        defaultValues: {
            name: '',
            description: '',
            includesRam: false,
        },
    });

    const onSubmit = async (data: CreateSnapshotForm) => {
        try {
            await createSnapshot(serverUuid, data);
            toast.success('Snapshot creation started');
            setOpen(false);
            form.reset();
            onSuccess();
        } catch (e: any) {
            toast.error(e.response?.data?.message || 'Failed to create snapshot');
        }
    };

    return (
        <Credenza open={open} onOpenChange={setOpen}>
            <CredenzaTrigger asChild>
                <Button>
                    <IconPlus className="mr-2 h-4 w-4" /> Create Snapshot
                </Button>
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Create Snapshot</CredenzaTitle>
                    <CredenzaDescription>
                        Create a new snapshot of the current server state.
                    </CredenzaDescription>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                        <InputForm
                            name="name"
                            label="Name"
                            placeholder="snap-1"
                        />
                        <InputForm
                            name="description"
                            label="Description (Optional)"
                        />
                        <CheckboxItemForm
                            name="includesRam"
                            label="Include RAM"
                        />
                        <CredenzaFooter>
                            <FormButton className="w-full">
                                Create
                            </FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    );
}
