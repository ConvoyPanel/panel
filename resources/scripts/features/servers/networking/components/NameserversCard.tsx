import { useMutation, useQueryClient } from '@tanstack/react-query'
import { IconPlus, IconTrash } from '@tabler/icons-react'
import { AxiosError } from 'axios'
import { useEffect, useMemo, useState } from 'react'
import { toast } from 'sonner'

import {
    nameserverQueries,
    updateNameservers,
    useNameservers,
} from '@/features/servers/networking/api.ts'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import Skeleton from '@/components/ui/Skeleton.tsx'

const errorMessage = (e: unknown, fallback: string): string =>
    e instanceof AxiosError && e.response?.data?.message
        ? e.response.data.message
        : fallback

interface Props {
    uuid: string
}

const NameserversCard = ({ uuid }: Props) => {
    const queryClient = useQueryClient()
    const { data: nameservers, isLoading } = useNameservers(uuid)

    const [values, setValues] = useState<string[]>([])

    useEffect(() => {
        if (nameservers) setValues(nameservers)
    }, [nameservers])

    const dirty = useMemo(
        () =>
            nameservers
                ? JSON.stringify(values) !== JSON.stringify(nameservers)
                : false,
        [values, nameservers]
    )

    const canSave = values.every(v => v.trim().length > 0)

    const { mutate: save, isPending } = useMutation({
        mutationFn: () =>
            updateNameservers(
                uuid,
                values.map(v => v.trim()).filter(v => v.length > 0)
            ),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: nameserverQueries.all(uuid),
            })
            toast.success('Nameservers updated')
        },
        onError: e =>
            toast.error(errorMessage(e, 'Failed to update nameservers')),
    })

    return (
        <Card>
            <CardHeader>
                <CardTitle>DNS Nameservers</CardTitle>
                <CardDescription>
                    Resolvers applied to the server via cloud-init.
                </CardDescription>
            </CardHeader>
            <CardContent className={'space-y-2'}>
                {isLoading ? (
                    <Skeleton className={'h-24 w-full'} />
                ) : (
                    <>
                        {values.map((value, index) => (
                            <div
                                key={index}
                                className={'flex items-center gap-2'}
                            >
                                <Input
                                    value={value}
                                    placeholder={'e.g. 1.1.1.1'}
                                    className={'font-mono'}
                                    onChange={e =>
                                        setValues(prev =>
                                            prev.map((v, i) =>
                                                i === index ? e.target.value : v
                                            )
                                        )
                                    }
                                />
                                <Button
                                    variant={'ghost'}
                                    size={'icon'}
                                    className={'shrink-0'}
                                    onClick={() =>
                                        setValues(prev =>
                                            prev.filter((_, i) => i !== index)
                                        )
                                    }
                                >
                                    <IconTrash className={'h-4 w-4'} />
                                </Button>
                            </div>
                        ))}
                        <Button
                            variant={'outline'}
                            size={'sm'}
                            onClick={() => setValues(prev => [...prev, ''])}
                        >
                            <IconPlus className={'size-4'} />
                            Add nameserver
                        </Button>
                    </>
                )}
            </CardContent>
            {dirty && (
                <CardFooter className={'flex justify-end gap-3'}>
                    <Button
                        variant={'outline'}
                        onClick={() => nameservers && setValues(nameservers)}
                        disabled={isPending}
                    >
                        Reset
                    </Button>
                    <Button
                        onClick={() => save()}
                        loading={isPending}
                        disabled={!canSave}
                    >
                        Save changes
                    </Button>
                </CardFooter>
            )}
        </Card>
    )
}

export default NameserversCard
